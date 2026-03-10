<?php

/**
 * Personio Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @author    Christopher Brandt <christopher.brandt@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PersonioBundle\Import;

use \Exception;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\Message;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use numero2\PersonioBundle\Event\PersonioEvents;
use numero2\PersonioBundle\Event\PersonioParseEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;


class PersonioImport {


    /**
     * @var int
     */
    public const STATUS_ERROR = 0;
    public const STATUS_NEW = 1;
    public const STATUS_UPDATE = 2;


    /**
     * @var \Contao\CoreBundle\Framework\ContaoFramework
     */
    private ContaoFramework $framework;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private Connection $connection;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var \Contao\CoreBundle\Routing\ScopeMatcher
     */
    private ScopeMatcher $scopeMatcher;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;


    public function __construct( ContaoFramework $framework, Connection $connection, RequestStack $requestStack, ScopeMatcher $scopeMatcher, LoggerInterface $logger, TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher ) {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;

        $this->framework->initialize();
    }


    /**
     * Imports all available positions for the current archive
     *
     * @throws \Exception
     */
    public function importCurrentArchive(): void {
        $request = $this->requestStack->getCurrentRequest();
        $id = (int) Input::get('id');

        if( !$request
            || !$this->scopeMatcher->isBackendRequest($request)
            || !$id ) {
            return;
        }

        $archive = NewsArchiveModel::findOneById($id);

        if( !$archive || !$archive->personio_xml_uri ) {
            throw new \RuntimeException('News archive ID ' . $id . ' is not configured for use with Personio');
        }

        $this->importPositionsForArchive($archive, false);

        $params = $request->query->all();

        // remove so we are not stuck in a loop
        unset($params['key']);

        $target = $request->getPathInfo();

        if( !empty($params) ) {
            $target .= '?' . http_build_query($params);
        }

        Controller::redirect($target);
    }


    /**
     * Imports all available positions for the given archive
     *
     * @param \Contao\NewsArchiveModel $archive
     * @param bool $silent Indicates whether to show messages in the
     *     backend or not
     */
    private function importPositionsForArchive( NewsArchiveModel $archive, bool $silent = true ): void {

        $aResult = $this->importXML($archive->id, $archive->personio_xml_uri);

        if( !$silent ) {

            if( is_null($aResult) ) {

                Message::addError($this->translator->trans('personio.msg.import_error',
                        [$archive->personio_xml_uri],
                        'contao_default'));

            } else {

                Message::addInfo($this->translator->trans('personio.msg.import_success',
                            [
                                $aResult[self::STATUS_NEW],
                                $aResult[self::STATUS_UPDATE]
                            ],
                            'contao_default'));
            }

        } else {

            if( is_null($aResult) ) {

                $this->logger->log(LogLevel::ERROR,
                    'Could not import job positions for news archive ID '
                        . $archive->id,
                    [
                        'contao' => new ContaoContext(__METHOD__,
                            ContaoContext::ERROR),
                    ]);

            } else {

                $this->logger->log(LogLevel::INFO,
                    sprintf('Imported job positions from %s (%d new / %d updated)',
                        $archive->personio_xml_uri,
                        $aResult[self::STATUS_NEW],
                        $aResult[self::STATUS_UPDATE]),
                    [
                        'contao' => new ContaoContext(__METHOD__,
                            ContaoContext::GENERAL),
                    ]);
            }
        }
    }


    /**
     * Imports the given XML for the given news archive
     *
     * @param int $archiveID ID of the news archive to import entries for
     * @param string $uri The URI where the XML is located at
     *
     * @return array|null
     */
    private function importXML( int $archiveID, string $uri ): ?array {

        if( !$archiveID || !$uri ) {
            return null;
        }

        $sXML = "";

        try {

            $sXML = file_get_contents($uri);

        } catch(Exception $e) {

            return null;
        }

        if( $sXML ) {

            $this->connection->executeStatement("UPDATE " . NewsModel::getTable()
                    . " SET published = '0'"
                    . " WHERE personio_id != ''"
                    . " AND pid = :pid",
                ['pid' => $archiveID]);

            $oXML = null;
            $oXML = simplexml_load_string($sXML,
                'SimpleXMLElement',
                LIBXML_NOCDATA);
            $sXML = json_encode($oXML);
            $oXML = json_decode($sXML);

            $aResults = [
                self::STATUS_ERROR => 0,
                self::STATUS_NEW => 0,
                self::STATUS_UPDATE => 0,
            ];

            if( $oXML->position ) {

                if( !is_array($oXML->position) ) {
                    $oXML->position = [$oXML->position];
                }

                foreach( $oXML->position as $position ) {

                    $status = $this->importPosition($archiveID, $position);
                    $aResults[$status] += 1;
                }

                return $aResults;
            }
        }

        return null;
    }


    /**
     * Imports a single job position
     *
     * @param int $archiveID ID of the news archive to import entries for
     * @param object $position
     *
     * @return int Status code
     */
    private function importPosition( int $archiveID, object $position ): int {

        $archive = null;
        $archive = NewsArchiveModel::findById($archiveID);

        // find existing news...
        $news = null;
        $news = NewsModel::findOneBy(['pid=?', 'personio_id=?'],
            [$archiveID, $position->id]);

        //... or create a new one
        if( !$news ) {

            $news = new NewsModel();

            $news->pid = $archiveID;
            $news->personio_id = $position->id;
            $news->tstamp = time();
            $news->author = $archive->personio_author;
            $news->source = 'default';
            $news->published = false;
        }

        $isUpdate = (bool) $news->id;

        // iterate all properties
        foreach( $position as $key => $value ) {

            // title
            if( $key == 'name' ) {

                $news->headline = $value;

            // tstamp
            } else if( $key == 'createdAt' ) {

                $news->date = $news->time = strtotime($value);

            // descriptions
            } else if( $key == 'jobDescriptions' ) {

                if( !empty($value->jobDescription) ) {

                    // make sure we have an id to work with
                    if( !$news->id ) {
                        $news->save();
                    }

                    $oOldCTE = null;
                    $oOldCTE = ContentModel::findBy(['ptable=?', 'pid=?', 'type=?'],
                        [NewsModel::getTable(), $news->id, 'text'],
                        ['order' => 'sorting ASC']);

                    $sorting = 128;

                    foreach( $value->jobDescription as $i => $d ) {

                        $oContent = null;

                        // prepare / clean description text
                        $text = $this->cleanMarkup($d->value);

                        // use the nth old element …
                        if( $oOldCTE && $oOldCTE->next() ) {

                            $oContent = $oOldCTE->current();

                        // … or create a new one
                        } else {

                            $oContent = new ContentModel();
                            $oContent->ptable = NewsModel::getTable();
                            $oContent->pid = $news->id;
                            $oContent->sorting = $sorting;
                        }

                        $oContent->tstamp = time();
                        $oContent->type = 'text';

                        $oContent->headline = serialize([
                            'unit' => 'h2',
                            'value' => $d->name,
                        ]);

                        $oContent->text = $text;

                        $oContent->save();

                        // add reference to the saved ContentElement
                        // for later use in events
                        $d->contentElementID = $oContent->id;

                        $sorting += 128;
                    }
                }
            }
        }

        $news->published = '1';

        // set entry in main language
        if( class_exists("Terminal42\ChangeLanguage\Terminal42ChangeLanguageBundle") && !empty($archive->master) ) {

            $newsMainLang = null;
            $newsMainLang = NewsModel::findOneBy(['pid=?', 'personio_id=?'],
                [$archive->master, $position->id]);

            if( $newsMainLang ) {
                $news->languageMain = $newsMainLang->id;
            }
        }

        $event = new PersonioParseEvent($position, $news, $isUpdate);
        $this->eventDispatcher->dispatch($event, PersonioEvents::IMPORT_ADVERTISEMENT);
        $news = $event->getNews();

        $news->save();

        return $isUpdate ? self::STATUS_UPDATE : self::STATUS_NEW;
    }


    /**
     * Make sure the given string contains proper markup
     *
     * @param string $strContent
     *
     * @return string
     */
    private function cleanMarkup( string $strContent ): string {

        $strContent = trim($strContent);

        if( substr($strContent, 0, 1) != '<' ) {
            $strContent = '<p>' . $strContent . '</p>';
        }

        return $strContent;
    }
}