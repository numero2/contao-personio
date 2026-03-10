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


namespace numero2\PersonioBundle\EventListener\DataContainer;

use Contao\ArrayUtil;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\NewsArchiveModel;
use Symfony\Component\HttpFoundation\RequestStack;


class NewsListener {


    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var Contao\CoreBundle\Routing\ScopeMatcher
     */
    private ScopeMatcher $scopeMatcher;

    /**
     * Constructor
     */
    public function __construct( RequestStack $requestStack, ScopeMatcher $scopeMatcher ) {

        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }


    /**
     * Adds global operations to the current table
     *
     * @param DataContainer $dc
     */
    #[AsCallback('tl_news', target:'config.onload')]
    public function addGlobalOperations( DataContainer $dc ):void {

        $request = $this->requestStack->getCurrentRequest();
        $id = $dc->id;

        if( $request && $this->scopeMatcher->isBackendRequest($request) && $id ) {

            $oArchive = NULL;
            $oArchive = NewsArchiveModel::findOneById($id);

            if( $oArchive && $oArchive->personio_xml_uri ) {

                ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_news']['list']['global_operations'], 1, [
                    'personio_import' => [
                        'primary'   => 'true'
                    ,   'label'     => &$GLOBALS['TL_LANG']['tl_news']['personio_import']
                    ,   'href'      => 'key=personio_import'
                    ,   'icon'      => 'rss.svg'
                    ]
                ]);
            }
        }
    }
}