<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Iterator;

interface EventStore extends ReadOnlyEventStore
{
    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void;

    public function create(Stream $stream): void;

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void;

    public function delete(StreamName $streamName): void;
}
