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

use Contao\NewsModel;
use Symfony\Contracts\EventDispatcher\Event;


class PersonioParseEvent {


    /**
     * @var object
     */
    private $position;

    /**
     * @var Contao\NewsModel;
     */
    private $news;

    /**
     * @var bool
     */
    private $isUpdate;

    public function __construct( object $position, NewsModel $news, bool $isUpdate ) {

        $this->position = $position;
        $this->news = $news;
        $this->isUpdate = $isUpdate;
    }


    public function getPosition(): object {
        return $this->position;
    }


    public function getNews(): NewsModel {
        return $this->news;
    }

    public function isUpdate(): bool {
        return $this->isUpdate;
    }
}