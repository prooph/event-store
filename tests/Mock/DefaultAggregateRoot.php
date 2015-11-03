<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/22/15 - 10:58 PM
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Class DefaultAggregateRoot
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class DefaultAggregateRoot implements DefaultAggregateRootContract
{
    private $historyEvents = [];

    /**
     * @var int
     */
    private $version = 0;

    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param \Iterator $historyEvents
     * @return DefaultAggregateRootContract
     */
    public static function reconstituteFromHistory(\Iterator $historyEvents)
    {
        $self = new self();

        $self->historyEvents = iterator_to_array($historyEvents);

        return $self;
    }

    /**
     * @return array
     */
    public function getHistoryEvents()
    {
        return $this->historyEvents;
    }

    /**
     * @return string
     */
    public function getId()
    {
        // not required for this mock
    }

    /**
     * @return Message[]
     */
    public function popRecordedEvents()
    {
        // not required for this mock
    }

    /**
     * @param $event
     */
    public function apply($event)
    {
        // not required for this mock
    }
}
