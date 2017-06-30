<?php

use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

require __DIR__ . '/../../vendor/autoload.php';

$eventStore = new InMemoryEventStore();
$eventStore->create(new Stream(new StreamName('user-123'), new ArrayIterator([])));

$projectionManager = new InMemoryProjectionManager($eventStore);
$projection = $projectionManager->createProjection(
    'test_projection',
    [
        Projector::OPTION_PCNTL_DISPATCH => true
    ]
);
pcntl_signal(SIGQUIT, function () use ($projection) {
    $projection->stop();
    exit(SIGUSR1);
});
$projection
    ->fromStream('user-123')
    ->whenAny(function () {})
    ->run();
