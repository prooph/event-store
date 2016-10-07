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

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/event/QuickStartSucceeded.php';

/**
 * The event store has two dependencies:
 *
 * - Prooph\EventStore\Adapter\Adapter
 * - Prooph\Common\Event\ActionEventEmitter
 *
 * Here we use the InMemoryAdapter but in a real project
 * you need to set up one of the available
 * persistence adapters.
 *
 * Prooph\Common\Event\ActionEventEmitter is an interface
 * that encapsulates functionality of an event dispatcher.
 * You can use the one provided by prooph/common or
 * you write a wrapper for the event dispatcher used
 * by your web framework.
 */
$eventStore = new \Prooph\EventStore\EventStore(
    new \Prooph\EventStore\Adapter\InMemoryAdapter(),
    new \Prooph\Common\Event\ProophActionEventEmitter()
);

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
$quickStartSucceeded = \Example\Event\QuickStartSucceeded::withSuccessMessage('It works');

/**
 * Use the event store to manage transactions.
 * It will delegate transaction handling to
 * the underlying adapter if the adapter supports
 * transactions.
 */
$eventStore->beginTransaction();

/**
 * Events are organized in so called event streams.
 * An event stream is a logical unit for a group of events.
 */
$streamName = new \Prooph\EventStore\Stream\StreamName('event_stream');

$singleStream = new \Prooph\EventStore\Stream\Stream($streamName, new ArrayIterator());

/**
 * As we are using the InMemoryAdapter we have to create the event stream
 * each time running the quick start. With a real persistence adapter this
 * is not required. In this case you should create the stream once. For example
 * with the help of a migration script.
 *
 * Note: For more details see the docs of the adapter you want to use.
 */
$eventStore->create($singleStream);

/**
 * Now we can easily add events to the stream ...
 */
$eventStore->appendTo($streamName, new ArrayIterator([$quickStartSucceeded /*, ...*/]));

/**
 * Next step would be to commit the transaction.
 * But let's attach a plugin first that prints some information about currently added events.
 * Plugins are simple event listeners. See the docs of prooph/common for more details about event listeners.
 */
$eventStore->getActionEventEmitter()->attachListener(
    'commit.post', //Most of the event store methods provide pre and post hooks
    function (\Prooph\Common\Event\ActionEvent $actionEvent): void {
        /**
         * In the *commit.post* action event a plugin has access to
         * all recorded events which were added in the current committed transaction.
         * It is the ideal place to attach a domain event dispatcher.
         * We only use a closure here to print the recorded events in the terminal
         */
        $recordedEvents = $actionEvent->getParam('recordedEvents');

        foreach ($recordedEvents as $recordedEvent) {
            echo sprintf(
                "Event with name %s was recorded. It occurred on %s ///\n\n",
                $recordedEvent->messageName(),
                $recordedEvent->createdAt()->format('Y-m-d H:i:s')
            );
        }
    }
);

//Now let's commit the transaction. Our closure plugin will print the event information
//so check your terminal
$eventStore->commit();

/**
 * Once committed you can of course also load a set of events or the entire stream
 * Use $eventStore->loadEventsByMetadataFrom($streamName, $metadata, $minVersion);
 * to load a list of events
 *
 * or the $eventStore->load($streamName); to get all events
 */
$persistedEventStream = $eventStore->load($streamName);

foreach ($persistedEventStream->streamEvents() as $event) {
    if ($event instanceof \Example\Event\QuickStartSucceeded) {
        echo $event->getText();
    }
}
