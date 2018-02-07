<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Iterator;

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
     * @var array
     */
    protected $metadata = [];

    public function __construct(StreamName $streamName, Iterator $streamEvents, array $metadata = [])
    {
        $this->streamName = $streamName;
        $this->streamEvents = $streamEvents;
        $this->metadata = $metadata;
    }

    public function streamName(): StreamName
    {
        return $this->streamName;
    }

    public function streamEvents(): Iterator
    {
        return $this->streamEvents;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }
}
