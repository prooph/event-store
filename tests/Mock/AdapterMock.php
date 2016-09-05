<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Mock;

use DateTimeInterface;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

/**
 * Class AdapterMock
 * @package ProophTest\EventStore\Mock
 */
final class AdapterMock implements Adapter
{
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getInjectedOptions()
    {
        return $this->options;
    }

    /**
     * @param Stream $stream
     * @return void
     */
    public function create(Stream $stream)
    {
        throw new \BadMethodCallException(__CLASS__ . '::' . __METHOD__);
    }

    /**
     * @param StreamName $streamName
     * @param Message[] $domainEvents
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException If stream does not exist
     * @return void
     */
    public function appendTo(StreamName $streamName, Iterator $domainEvents)
    {
        throw new \BadMethodCallException(__CLASS__ . '::' . __METHOD__);
    }

    /**
     * @param StreamName $streamName
     * @param null|int $minVersion Minimum version an event should have
     * @return Stream|null
     */
    public function load(StreamName $streamName, $minVersion = null)
    {
        throw new \BadMethodCallException(__CLASS__ . '::' . __METHOD__);
    }

    /**
     * @param StreamName $streamName
     * @param array $metadata If empty array is provided, then all events should be returned
     * @param null|int $minVersion Minimum version an event should have
     * @return Message[]
     */
    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, $minVersion = null)
    {
        throw new \BadMethodCallException(__CLASS__ . '::' . __METHOD__);
    }

    /**
     * @param StreamName $streamName
     * @param array $metadata
     * @param null $minVersion
     * @return Iterator
     */
    public function loadEvents(StreamName $streamName, array $metadata = [], $minVersion = null)
    {
        throw new \BadMethodCallException(__CLASS__ . '::' . __METHOD__);
    }

    /**
     * @param StreamName $streamName
     * @param DateTimeInterface|null $since
     * @param array $metadata
     * @return Iterator
     */
    public function replay(StreamName $streamName, DateTimeInterface $since = null, array $metadata = [])
    {
        throw new \BadMethodCallException(__CLASS__ . '::' . __METHOD__);
    }
}
