<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Personio Bundle
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\PersonioBundle;

use Contao\ContentModel;
use Contao\Controller;
use Contao\Database;
use Contao\Input;
use Contao\Message;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\System;


class Importer extends Controller {


    const STATUS_ERROR = 0;
    const STATUS_NEW = 1;
    const STATUS_UPDATE = 2;


    /**
     * Runs the import
     */
    public function run(): void {

        $aNewsArchives = [];

        // get current archive
        if( TL_MODE == 'BE' ) {

            if( $archive = \Input::get('id') ) {

                $oArchive = NULL;
                $oArchive = NewsArchiveModel::findById($archive);

                if( $oArchive && $oArchive->personio_xml_uri ) {
                    $aNewsArchives[ $oArchive->id ] = $oArchive->personio_xml_uri;
                }
            }

        // iterate all archives
        } else {

            $oArchives = NULL;
            $oArchives = NewsArchiveModel::findBy(["personio_xml_uri!=''"],null);

            if( $oArchives ) {

                while( $oArchives->next() ) {
                    $aNewsArchives[ $oArchives->id ] = $oArchives->personio_xml_uri;
                }
            }
        }

        if( !empty($aNewsArchives) ) {

            foreach( $aNewsArchives as $archiveID => $uri ) {

                $aResult = [];
                $aResult = $this->importXML($archiveID, $uri);

                if( TL_MODE == 'BE' ) {

                    if( is_null($aResult) ) {

                        Message::addError(sprintf(
                            $GLOBALS['TL_LANG']['PERSONIO']['MSG']['import_error']
                        ,   $uri
                        ));

                    } else {

                        Message::addInfo(sprintf(
                            $GLOBALS['TL_LANG']['PERSONIO']['MSG']['import_success']
                        ,   $aResult[self::STATUS_NEW]
                        ,   $aResult[self::STATUS_UPDATE]
                        ));
                    }

                } else {

                    System::log(sprintf('Imported job applications from %s', $uri), __METHOD__, TL_CRON);
                }
            }
        }

        if( TL_MODE == 'BE' ) {
            $this->redirect($this->getReferer());
        } else {

        }
    }


    /**
     * Imports the given XML for the given news archive
     *
     * @param int $archiveID ID of the news archive to import entries for
     * @param string $uri The URI where the XML is located at
     *
     * @return array
     */
    private function importXML( int $archiveID, string $uri ): ?array {

        if( !$archiveID || !$uri ) {
            return false;
        }

        $sXML = "";

        try {

            $sXML = file_get_contents($uri);

        } catch( \Exception $e ) {

            return null;
        }

        if( $sXML ) {

            // hide all job listing in current archive to make sure deleted
            // listings are not shown anymore
            Database::getInstance()->query("UPDATE ".NewsModel::getTable()." SET published = 0 WHERE personio_id != '' AND pid = '".$archiveID."'");

            $oXML = NULL;
            $oXML = simplexml_load_string($sXML, 'SimpleXMLElement', LIBXML_NOCDATA);
            $sXML = json_encode($oXML);
            $oXML = json_decode($sXML);

            $aResults = [
                self::STATUS_ERROR => 0
            ,   self::STATUS_NEW => 0
            ,   self::STATUS_UPDATE => 0
            ];

            if( $oXML->position ) {

                if( !is_array($oXML->position) ) {
                    $oXML->position = [ $oXML->position ];
                }

                foreach( $oXML->position as $position ) {

                    $status = $this->importPosition($archiveID, $position);
                    $aResults[ $status ] += 1;
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
     * @return int|null
     */

    private function importPosition( int $archiveID, object $position ): ?int {

        $this->import('Database');

        // find existing news...
        $oNews = NULL;
        $oNews = NewsModel::findOneBy(['pid=?','personio_id=?'],[$archiveID,$position->id]);

        //... or create a new one
        if( !$oNews ) {

            $oArchive = NULL;
            $oArchive = NewsArchiveModel::findById($archiveID);

            $oNews = new NewsModel();

            $oNews->pid = $archiveID;
            $oNews->personio_id = $position->id;
            $oNews->tstamp = time();
            $oNews->author = $oArchive->personio_author;
            $oNews->source = 'default';
            $oNews->published = false;
        }

        $isUpdate = (bool) $oNews->id;

        // iterate all properties
        foreach( $position as $key => $value ) {

            // title
            if( $key == 'name' ) {

                $oNews->headline = $value;

            // location
            } else if( $key == 'office' ) {

                // TODO: location field might not exist
                if( $this->Database->fieldExists('location', NewsModel::getTable()) ) {
                    $oNews->location = $value;
                }

            // tstamp
            } else if( $key == 'createdAt' ) {

                $oNews->date = $oNews->time = strtotime($value);

            // subheadline
            } else if( $key == 'recruitingCategory' ) {

                //$oNews->subheadline = $value;

            // descriptions
            } else if( $key == 'jobDescriptions' ) {

                if( !empty($value->jobDescription) ) {

                    $oOldCTE = NULL;

                    foreach( $value->jobDescription as $i => $d ) {

                        $oContent = NULL;

                        // prepare / clean description text
                        $text = $this->cleanMarkup($d->value);

                        // use first description only for teaser
                        if( $i === 0 ) {

                            $oNews->teaser = $text;

                            // make sure we have an id to work with
                            if( !$oNews->id ) {
                                $oNews->save();
                            }

                            // find old content elements
                            if( !$oOldCTE ) {

                                $oOldCTE = ContentModel::findBy(
                                    ['ptable=?','pid=?','type=?']
                                ,   [NewsModel::getTable(), $oNews->id, 'text']
                                );
                            }

                            continue;
                        }

                        // use the nth old element …
                        if( $oOldCTE && $oOldCTE->next() ) {

                            $oContent = $oOldCTE->current();

                        // … or create a new one
                        } else {

                            $oContent = new ContentModel();
                            $oContent->ptable = NewsModel::getTable();
                            $oContent->pid = $oNews->id;
                        }

                        $oContent->tstamp = time();
                        $oContent->type = 'text';

                        $oContent->headline = serialize([
                            'unit' => 'h3'
                        ,   'value' => $d->name
                        ]);

                        $oContent->text = $text;

                        $oContent->save();
                    }
                }
            }
        }

        $oNews->published = true;
        $oNews->save();

        if( $isUpdate ) {
            return self::STATUS_UPDATE;
        } else {
            return self::STATUS_NEW;
        }
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

        // make sure "text-only" is encapsuled in a <p>
        if( substr($strContent, 0, 1) != '<' ) {
            $strContent = '<p>'.$strContent.'</p>';
        }

        return $strContent;
    }
}