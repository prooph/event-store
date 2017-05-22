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

    /**
     * @test
     */
    public function it_throws_on_invalid_value_for_in_operator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an array for the operator IN');

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('foo', Operator::IN(), 'bar', FieldType::METADATA());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_value_for_not_in_operator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an array for the operator NOT_IN.');

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('foo', Operator::NOT_IN(), 'bar', FieldType::METADATA());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_value_for_regex_operator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be a string for the regex operator.');

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('foo', Operator::REGEX(), false, FieldType::METADATA());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_value_for_equals_operator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must have a scalar type for the operator EQUALS.');

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), ['bar' => 'baz'], FieldType::METADATA());
    }
}
