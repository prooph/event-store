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

namespace ProophTest\EventStore\Metadata;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Metadata\FieldType;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;

class MetadataMatcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_on_invalid_field_for_message_property(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid message property "foo" given');

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar', FieldType::MESSAGE_PROPERTY());
    }
}
