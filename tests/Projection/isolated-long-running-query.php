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

use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\TestDomainEvent;

require __DIR__ . '/../../vendor/autoload.php';

$eventStore = new InMemoryEventStore();
$events = [];

for ($i = 0; $i < 100; $i++) {
    $events[] = TestDomainEvent::with(['test' => 1], $i);
    $i++;
}

$eventStore->create(new Stream(new StreamName('user-123'), new ArrayIterator($events)));

$projectionManager = new InMemoryProjectionManager($eventStore);
$query = $projectionManager->createQuery(
    [
        Query::OPTION_PCNTL_DISPATCH => true,
    ]
);

\pcntl_signal(SIGQUIT, function () use ($query) {
    $query->stop();
    exit(SIGUSR1);
});

$query
    ->fromStreams('user-123')
    ->whenAny(function () {
        \usleep(500000);
    })
    ->run();
