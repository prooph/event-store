<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/22/15 - 10:05 PM
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Interface DefaultAggregateRootContract
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface DefaultAggregateRootContract
{
    /**
     * @param \Iterator $historyEvents
     * @return DefaultAggregateRootContract
     */
    public static function reconstituteFromHistory(\Iterator $historyEvents);

    /**
     * @return int
     */
    public function getVersion();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return Message[]
     */
    public function popRecordedEvents();

    /**
     * @param $event
     */
    public function apply($event);
}
