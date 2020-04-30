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


namespace numero2\PersonioBundle\DCAHelper;

use Contao\DataContainer;
use Contao\NewsArchiveModel;


class News {


    /**
     * Constructor
     */
    public function __construct() {}


    /**
     * Adds global operations to the current table
     *
     * @param DataContainer $dc
     */
    public function addGlobalOperations( DataContainer $dc ):void {

        if( $dc->id ) {

            $oArchive = NULL;
            $oArchive = NewsArchiveModel::findOneById($dc->id);

            if( $oArchive && $oArchive->personio_xml_uri ) {

                array_insert($GLOBALS['TL_DCA']['tl_news']['list']['global_operations'], 1, [
                    'personio_import' => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_news']['personio_import']
                    ,   'href'      => 'key=personio_import'
                    ,   'icon'      => 'rss.svg'
                    ]
                ]);
            }
        }
    }
}