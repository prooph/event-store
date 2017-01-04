<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Stream;

use Iterator;

/**
 * Class Stream
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <contact@prooph.de>
 */
class Stream
{
    /**
     * @var StreamName
     */
    protected $streamName;

    /**
     * @var Iterator
     */
    protected $streamEvents;

    /**
     * @param StreamName $streamName
     * @param Iterator $streamEvents
     */
    public function __construct(StreamName $streamName, Iterator $streamEvents)
    {
        $this->streamName = $streamName;

        $this->streamEvents = $streamEvents;
    }

    /**
     * @return StreamName
     */
    public function streamName()
    {
        return $this->streamName;
    }

    /**
     * @return Iterator
     */
    public function streamEvents()
    {
        return $this->streamEvents;
    }
}
