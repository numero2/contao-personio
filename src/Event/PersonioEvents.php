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


namespace numero2\PersonioBundle\Event;


final class PersonioEvents {

    /**
     * The contao.tags_get_list is triggered whenever we need a list of tags.
     *
     * @see numero2\PersonioBundle\Event\PersonioParseEvent
     */
    public const IMPORT_ADVERTISEMENT = 'contao.personio_import_advertisement';
}