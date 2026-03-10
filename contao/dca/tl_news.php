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


/**
 * Add fields to tl_news
 */
$GLOBALS['TL_DCA']['tl_news']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_news']['fields']
,   [
    'personio_id' => [
            'exclude'      => true
        ,   'inputType'    => 'text'
        ,   'sql'          => "varchar(255) NOT NULL default ''"
        ]
    ]);