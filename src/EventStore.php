<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore;

use Iterator;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

interface EventStore
{
    public function getRecordedEvents(): Iterator;

    public function fetchStreamMetadata(StreamName $streamName): array;

    public function hasStream(StreamName $streamName): bool;

    public function create(Stream $stream): bool;

    public function appendTo(StreamName $streamName, Iterator $streamEvents): bool;

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream;

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream;
}
