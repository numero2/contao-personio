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
 * BACK END MODULES
 */
$GLOBALS['BE_MOD']['content']['news']['personio_import'] = [\numero2\PersonioBundle\Importer::class, 'run'];


/**
 * CRONJOBS
 */
$GLOBALS['TL_CRON']['daily'][] = [\numero2\PersonioBundle\Importer::class, 'run'];