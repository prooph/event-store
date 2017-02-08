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

namespace ProophTest\EventStore\Plugin;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Plugin\UpcastingPlugin;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\Upcasting\NoOpEventUpcaster;
use Prooph\EventStore\Upcasting\SingleEventUpcaster;
use ProophTest\EventStore\ActionEventEmitterEventStoreTestCase;
use ProophTest\EventStore\Mock\EventLoggerPlugin;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use Zend\ServiceManager\ServiceManager;

class UpcastingPluginTest extends ActionEventEmitterEventStoreTestCase
{
    /**
     * @test
     */
    public function it_attaches_to_event_store(): void
    {
        $upcaster = new class extends SingleEventUpcaster {
            protected function canUpcast(Message $message): bool
            {
                return true;
            }

            protected function doUpcast(Message $message): array
            {
                return [$message->withAddedMetadata('key', 'value')];
            }

        };

        $plugin = new UpcastingPlugin($upcaster);
        $plugin->attachToEventStore($this->eventStore);

        $streamName = new StreamName('user');

        $this->eventStore->create(
            new Stream(
                $streamName,
                new \ArrayIterator([
                    UserCreated::with(
                        [
                            'name' => 'Alex',
                            'email' => 'contact@prooph.de',
                        ],
                        1
                    ),
                    UsernameChanged::with(
                        [
                            'name' => 'Sascha',
                        ],
                        2
                    ),
                ])
            )
        );

        $iterator = $this->eventStore->load($streamName);

        $this->assertTrue($iterator->valid());
        $this->assertEquals(['key' => 'value', '_aggregate_version' => 1], $iterator->current()->metadata());
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertEquals(['key' => 'value', '_aggregate_version' => 2], $iterator->current()->metadata());

        $iterator = $this->eventStore->loadReverse($streamName);

        $this->assertTrue($iterator->valid());
        $this->assertEquals(['key' => 'value', '_aggregate_version' => 2], $iterator->current()->metadata());
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertEquals(['key' => 'value', '_aggregate_version' => 1], $iterator->current()->metadata());
    }

    /**
     * @test
     */
    public function it_ignores_when_no_iterator_in_result(): void
    {
        $this->expectException(StreamNotFound::class);

        $plugin = new UpcastingPlugin(new NoOpEventUpcaster());
        $plugin->attachToEventStore($this->eventStore);

        $this->eventStore->load(new StreamName('user'));
    }
}
