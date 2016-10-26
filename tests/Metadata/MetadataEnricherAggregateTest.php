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

namespace ProophTest\EventStore\Metadata;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use ProophTest\EventStore\Mock\TestDomainEvent;
use ProophTest\EventStore\TestCase;
use Prophecy\Argument;

final class MetadataEnricherAggregateTest extends TestCase
{
    /**
     * @test
     */
    public function it_aggregates_metadata_enrichers(): void
    {
        // Mocks
        $metadataEnricher1 = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher2 = $this->prophesize(MetadataEnricher::class);

        // Class under test
        $metadataEnricherAgg = new MetadataEnricherAggregate([
            $metadataEnricher1->reveal(),
            $metadataEnricher2->reveal(),
        ]);

        // Initial payload and expected data
        $originalEvent = TestDomainEvent::with(['foo' => 'bar'], 1);
        $eventAfterEnricher1 = $originalEvent->withAddedMetadata('meta1', 'data1');
        $eventAfterEnricher2 = $eventAfterEnricher1->withAddedMetadata('meta2', 'data2');

        // Prepare mock
        $metadataEnricher1
            ->enrich(Argument::type(Message::class))
            ->shouldBeCalledTimes(1)
            ->willReturn($eventAfterEnricher1);

        $metadataEnricher2
            ->enrich(Argument::type(Message::class))
            ->shouldBeCalledTimes(1)
            ->willReturn($eventAfterEnricher2);

        // Call method under test
        $enrichedEvent = $metadataEnricherAgg->enrich($originalEvent);

        // Assertions
        $this->assertEquals($originalEvent->payload(), $enrichedEvent->payload());
        $this->assertEquals($originalEvent->version(), $enrichedEvent->version());
        $this->assertEquals($originalEvent->createdAt(), $enrichedEvent->createdAt());

        $expectedMetadata = ['meta1' => 'data1', 'meta2' => 'data2', '_version' => 1];
        $this->assertEquals($expectedMetadata, $enrichedEvent->metadata());
    }

    /**
     * @test
     */
    public function it_only_accept_correct_instances(): void
    {
        $this->expectException(\Assert\InvalidArgumentException::class);

        $metadataEnricherAggregate = new MetadataEnricherAggregate([
            $this->prophesize(MetadataEnricher::class)->reveal(),
            new \stdClass(),
            $this->prophesize(MetadataEnricher::class)->reveal(),
        ]);
    }
}
