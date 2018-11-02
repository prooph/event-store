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

namespace ProophTest\EventStore\Upcasting;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Upcasting\SingleEventUpcaster;

class SingleEventUpcasterTest extends TestCase
{
    /**
     * @test
     */
    public function it_upcasts(): void
    {
        $upcastedMessage = $this->prophesize(Message::class);
        $upcastedMessage = $upcastedMessage->reveal();

        $message = $this->prophesize(Message::class);
        $message->withAddedMetadata('key', 'value')->willReturn($upcastedMessage)->shouldBeCalled();
        $message = $message->reveal();

        $upcaster = $this->createUpcasterWhoCanUpcast();

        $messages = $upcaster->upcast($message);

        $this->assertInternalType('array', $messages);
        $this->assertNotEmpty($messages);
        $this->assertSame($upcastedMessage, $messages[0]);
    }

    /**
     * @test
     */
    public function it_does_not_upcast_when_impossible(): void
    {
        $message = $this->prophesize(Message::class);
        $message->withAddedMetadata('key', 'value')->shouldNotBeCalled();
        $message = $message->reveal();

        $upcaster = $this->createUpcasterWhoCannotUpcast();

        $messages = $upcaster->upcast($message);

        $this->assertInternalType('array', $messages);
        $this->assertNotEmpty($messages);
        $this->assertSame($message, $messages[0]);
    }

    protected function createUpcasterWhoCanUpcast(): SingleEventUpcaster
    {
        return new class() extends SingleEventUpcaster {
            protected function canUpcast(Message $message): bool
            {
                return true;
            }

            protected function doUpcast(Message $message): array
            {
                return [$message->withAddedMetadata('key', 'value')];
            }
        };
    }

    protected function createUpcasterWhoCannotUpcast(): SingleEventUpcaster
    {
        return new class() extends SingleEventUpcaster {
            protected function canUpcast(Message $message): bool
            {
                return false;
            }

            protected function doUpcast(Message $message): array
            {
                return [$message->withAddedMetadata('key', 'value')];
            }
        };
    }
}
