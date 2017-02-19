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

namespace Prooph\EventStore;

use Iterator;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\QueryFactory;

interface ReadOnlyEventStore
{
    public function fetchStreamMetadata(StreamName $streamName): array;

    public function hasStream(StreamName $streamName): bool;

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator;

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator;

    public function createQuery(QueryFactory $factory = null): Query;

    /**
     * @return StreamName[]
     */
    public function fetchStreamNames(
        ?string $filter,
        bool $regex,
        ?MetadataMatcher $metadataMatcher,
        int $limit,
        int $offset
    ): array;

    /**
     * @return string[]
     */
    public function fetchCategoryNames(?string $filter, bool $regex, int $limit, int $offset): array;

    /**
     * @return string[]
     */
    public function fetchProjectionNames(?string $filter, bool $regex, int $limit, int $offset): array;

    public function fetchProjectionStatus(string $name): ProjectionStatus;

    public function fetchProjectionStreamPositions(string $name): ?array;

    public function fetchProjectionState(string $name): array;
}
