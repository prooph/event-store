<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\QuickStart;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/event/QuickStartSucceeded.php';

use ArrayIterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\QuickStart\Event\QuickStartSucceeded;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;

/**
 * Here we use the InMemoryEventStore but in a real project
 * you need to chose another implementation.
 *
 * Prooph\Common\Event\ActionEventEmitter is an interface
 * that encapsulates functionality of an event dispatcher.
 * You can use the one provided by prooph/common or
 * you write a wrapper for the event dispatcher used
 * by your web framework.
 */
$eventEmitter = new ProophActionEventEmitter(TransactionalActionEventEmitterEventStore::ALL_EVENTS);

$eventStore = new ActionEventEmitterEventStore(new InMemoryEventStore(), $eventEmitter);

/**
 * We need a test event so let's create one.
 *
 * As a bare minimum events need to implement
 * Prooph\Common\Messaging\Message.
 *
 * Note: It is possible to use your own events
 * in your domain and use a translator to
 * convert them. We'll come to that later.
 */
$quickStartSucceeded = QuickStartSucceeded::withSuccessMessage('It works');

/**
 * Events are organized in so called event streams.
 * An event stream is a logical unit for a group of events.
 */
$streamName = new StreamName('event_stream');

$singleStream = new Stream($streamName, new ArrayIterator());

/**
 * As we are using the InMemoryEventStore we have to create the event stream
 * each time running the quick start. With a real persistence adapter this
 * is not required. In this case you should create the stream once. For example
 * with the help of a migration script.
 *
 * Note: For more details see the docs of the adapter you want to use.
 */
$eventStore->create($singleStream);

/**
 * Next step would be to commit the transaction.
 * But let's attach a plugin first that prints some information about currently added events.
 * Plugins are simple event listeners. See the docs of prooph/common for more details about event listeners.
 */
$eventStore->attach(
    ActionEventEmitterEventStore::EVENT_APPEND_TO, // InMemoryEventStore provides event hooks
    function (ActionEvent $actionEvent): void {
        /**
         * In the *commit.post* action event a plugin has access to
         * all recorded events which were added in the current committed transaction.
         * It is the ideal place to attach a domain event dispatcher.
         * We only use a closure here to print the recorded events in the terminal
         */
        $recordedEvents = $actionEvent->getParam('streamEvents');

        foreach ($recordedEvents as $recordedEvent) {
            echo \sprintf(
                "Event with name %s was recorded. It occurred on %s ///\n\n",
                $recordedEvent->messageName(),
                $recordedEvent->createdAt()->format('Y-m-d H:i:s')
            );
        }
    },
    -1000 // low priority, so after action happened
);

/**
 * Now we can easily add events to the stream ...
 */
$eventStore->appendTo($streamName, new ArrayIterator([$quickStartSucceeded /*, ...*/]));

/**
 * Once committed you can of course also load a set of events or the entire stream
 * Use $eventStore->loadEventsByMetadataFrom($streamName, $metadata, $minVersion);
 * to load a list of events
 *
 * or the $eventStore->load($streamName); to get all events
 */
$persistedEventStream = $eventStore->load($streamName);

foreach ($persistedEventStream as $event) {
    if ($event instanceof QuickStartSucceeded) {
        echo $event->getText();
    }
}
