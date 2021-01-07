<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Upcasting;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Upcasting\NoOpEventUpcaster;
use Prooph\EventStore\Upcasting\SingleEventUpcaster;
use Prooph\EventStore\Upcasting\Upcaster;
use Prooph\EventStore\Upcasting\UpcasterChain;
use Prophecy\PhpUnit\ProphecyTrait;

class UpcasterChainTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_chains_upcasts(): void
    {
        $upcastedMessage3 = $this->prophesize(Message::class);
        $upcastedMessage3 = $upcastedMessage3->reveal();

        $upcastedMessage2 = $this->prophesize(Message::class);
        $upcastedMessage2 = $upcastedMessage2->reveal();

        $upcastedMessage1 = $this->prophesize(Message::class);
        $upcastedMessage1->withAddedMetadata('key', 'other_value')->willReturn($upcastedMessage2)->shouldBeCalled();
        $upcastedMessage1->withAddedMetadata('key', 'yet_another_value')->willReturn($upcastedMessage3)->shouldBeCalled();
        $upcastedMessage1 = $upcastedMessage1->reveal();

        $message = $this->prophesize(Message::class);
        $message->withAddedMetadata('key', 'value')->willReturn($upcastedMessage1)->shouldBeCalled();
        $message = $message->reveal();

        $upcasterOne = $this->createUpcasterWhoCanUpcast();
        $upcasterTwo = new NoOpEventUpcaster();
        $upcasterThree = $this->createUpcasterWhoCanAlsoUpcast();

        $upcasterChain = new UpcasterChain($upcasterOne, $upcasterTwo, $upcasterThree);

        $messages = $upcasterChain->upcast($message);

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
        $this->assertSame($upcastedMessage2, $messages[0]);
        $this->assertSame($upcastedMessage3, $messages[1]);
    }

    /**
     * @test
     */
    public function it_doesnt_remove_messages_when_a_subsequent_upcaster_returns_fewer_messages()
    {
        $initialMessage = $this->prophesize(Message::class);
        $initialMessage->payload()->willReturn(['name' => 'initialMessage']);
        $initialMessage = $initialMessage->reveal();

        $subsequentMessage = $this->prophesize(Message::class);
        $subsequentMessage->payload()->willReturn(['name' => 'subsequentMessage']);
        $subsequentMessage = $subsequentMessage->reveal();

        $modifiedSubsequentMessage = $this->prophesize(Message::class);
        $modifiedSubsequentMessage->payload()->willReturn(['name' => 'subsequentMessage-modified']);
        $modifiedSubsequentMessage = $modifiedSubsequentMessage->reveal();

        $furtherSubsequentMessage = $this->prophesize(Message::class);
        $furtherSubsequentMessage->payload()->willReturn(['name' => 'furtherSubsequentMessage']);
        $furtherSubsequentMessage = $furtherSubsequentMessage->reveal();

        $upcasterOne = $this->prophesize(Upcaster::class);
        $upcasterOne->upcast($initialMessage)->willReturn([$initialMessage]);

        $upcasterTwo = $this->prophesize(Upcaster::class);
        $upcasterTwo->upcast($initialMessage)->willReturn([$initialMessage, $subsequentMessage]);

        $upcasterThree = $this->prophesize(Upcaster::class);
        $upcasterThree->upcast($initialMessage)->willReturn([$initialMessage]);
        $upcasterThree->upcast($subsequentMessage)->willReturn([$modifiedSubsequentMessage, $furtherSubsequentMessage]);

        $upcasterFour = new NoOpEventUpcaster();

        $upcasterChain = new UpcasterChain(
            $upcasterOne->reveal(),
            $upcasterTwo->reveal(),
            $upcasterThree->reveal(),
            $upcasterFour
        );

        $messages = $upcasterChain->upcast($initialMessage);

        $expectedMessagePayloads = [
            ['name' => 'initialMessage'],
            ['name' => 'subsequentMessage-modified'],
            ['name' => 'furtherSubsequentMessage'],
        ];
        $messagePayloads = \array_map(function (Message $message) {
            return $message->payload();
        }, $messages);

        $this->assertEquals($expectedMessagePayloads, $messagePayloads);
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

    protected function createUpcasterWhoCanAlsoUpcast(): SingleEventUpcaster
    {
        return new class() extends SingleEventUpcaster {
            protected function canUpcast(Message $message): bool
            {
                return true;
            }

            protected function doUpcast(Message $message): array
            {
                return [$message->withAddedMetadata('key', 'other_value'), $message->withAddedMetadata('key', 'yet_another_value')];
            }
        };
    }
}
