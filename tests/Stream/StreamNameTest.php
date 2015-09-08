<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 09/02/15 - 20:55
 */

namespace Prooph\EventStoreTest\Stream;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Stream\StreamName;

/**
 * Class StreamNameTest
 * @package Prooph\EventStoreTest\Stream
 */
final class StreamNameTest extends TestCase
{
    /**
     * @test
     */
    public function it_delegates_to_string()
    {
        $streamName = new StreamName('foo');
        $this->assertEquals('foo', (string) $streamName);
    }
}
