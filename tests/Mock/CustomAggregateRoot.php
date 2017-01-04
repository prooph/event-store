<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Class CustomAggregateRoot
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
final class CustomAggregateRoot implements CustomAggregateRootContract
{
    private $historyEvents = [];

    private $version = 0;

    /**
     * @return int
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @param \Iterator $historyEvents
     * @return CustomAggregateRootContract
     */
    public static function buildFromHistoryEvents(\Iterator $historyEvents)
    {
        $self = new self();

        $self->historyEvents = $historyEvents;

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
    public function identifier()
    {
        // not required for this mock
    }

    /**
     * @return Message[]
     */
    public function getPendingEvents()
    {
        // not required for this mock
    }
}
