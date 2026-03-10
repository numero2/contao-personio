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

use \numero2\PersonioBundle\Importer;


/**
 * BACK END MODULES
 */
$GLOBALS['BE_MOD']['content']['news']['personio_import'] = ['numero2_personio.import.personio', 'importCurrentArchive'];


/**
 * CRONJOBS
 */
$GLOBALS['TL_CRON']['daily'][] = ['numero2_personio.import.personio', 'importCurrentArchive'];