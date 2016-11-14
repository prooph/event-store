<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamProjection;

use Prooph\EventStore\InMemoryEventStore;

final class InMemoryStreamProjection extends AbstractStreamProjection
{
    use InMemoryQueryTrait;

    private $storage;

    public function __construct(InMemoryEventStore $eventStore, string $name, int $persistBatches, bool $enableEmit)
    {
        parent::__construct($eventStore, $name, $persistBatches, $enableEmit);

        $this->buildKnownStreams();
    }

    protected function load(): void
    {
        // InMemoryStreamProjection cannot load
    }

    protected function persist(): void
    {
        // InMemoryStreamProjection cannot persist
    }
}
