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

namespace ProophTest\EventStore;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\StreamName;

class StreamNameTest extends TestCase
{
    /**
     * @test
     */
    public function it_delegates_to_string(): void
    {
        $streamName = new StreamName('foo');
        $this->assertEquals('foo', (string) $streamName);
    }
}
