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
 * Modify palette of tl_news_archive
 */
$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['default'] = str_replace(
    ';{protected_legend'
,   ';{personio_legend},personio_enable_import;{protected_legend'
,   $GLOBALS['TL_DCA']['tl_news_archive']['palettes']['default']
);

$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['__selector__'][] = 'personio_enable_import';
$GLOBALS['TL_DCA']['tl_news_archive']['subpalettes']['personio_enable_import'] = 'personio_xml_uri,personio_author';


/**
 * Add fields to tl_news_archive
 */
$GLOBALS['TL_DCA']['tl_news_archive']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_news_archive']['fields']
,   [
        'personio_enable_import' => [
            'label'        => &$GLOBALS['TL_LANG']['tl_news_archive']['personio_enable_import']
        ,   'exclude'      => true
        ,   'filter'       => true
        ,   'inputType'    => 'checkbox'
        ,   'eval'         => ['submitOnChange'=>true]
        ,   'sql'          => "char(1) NOT NULL default ''"
        ]
    ,   'personio_xml_uri' => [
            'label'        => &$GLOBALS['TL_LANG']['tl_news_archive']['personio_xml_uri']
        ,   'exclude'      => true
        ,   'inputType'    => 'text'
        ,   'eval'         => ['mandatory'=>true, 'placeholder'=>'https://yourname.personio.de/xml', 'tl_class'=>'w50']
        ,   'sql'          => "varchar(255) NOT NULL default ''"
        ]
    ,   'personio_author' => [
            'label'        => &$GLOBALS['TL_LANG']['tl_news_archive']['personio_author']
        ,   'default'      => Contao\BackendUser::getInstance()->id
        ,   'exclude'      => true
        ,   'flag'         => 11
        ,   'inputType'    => 'select'
        ,   'foreignKey'   => 'tl_user.name'
        ,   'eval'         => ['mandatory'=>true, 'doNotCopy'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "int(10) unsigned NOT NULL default 0"
        ]
    ]
);