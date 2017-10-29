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

namespace ProophTest\EventStore;

use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\NonTransactionalInMemoryEventStore;

class NonTransactionalInMemoryEventStoreTest extends AbstractEventStoreTest
{
    use EventStoreTestStreamTrait;

    /**
     * @var InMemoryEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new NonTransactionalInMemoryEventStore();
    }
}
