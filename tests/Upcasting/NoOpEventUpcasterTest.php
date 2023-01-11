<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2023 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2023 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Upcasting;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Upcasting\NoOpEventUpcaster;
use Prophecy\PhpUnit\ProphecyTrait;

class NoOpEventUpcasterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_does_nothing_during_upcast(): void
    {
        $message = $this->prophesize(Message::class);
        $message = $message->reveal();

        $upcaster = new NoOpEventUpcaster();

        $messages = $upcaster->upcast($message);
        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
        $this->assertSame($message, $messages[0]);
    }
}
