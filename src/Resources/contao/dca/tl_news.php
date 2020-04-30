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


/**
 * Add callbacks to tl_news
 */
$GLOBALS['TL_DCA']['tl_news']['config']['onload_callback'][] = [ '\numero2\PersonioBundle\DCAHelper\News', 'addGlobalOperations' ];


/**
 * Add fields to tl_news
 */
$GLOBALS['TL_DCA']['tl_news']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_news']['fields']
,   [
       'personio_id' => [
            'exclude'      => true
        ,   'inputType'    => 'text'
        ,   'sql'          => "varchar(255) NOT NULL default ''"
        ]
    ]
);