<?php

/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use Prooph\EventStore\Exception\ProjectionNotFound;

interface ProjectionManager
{
    public function createQuery(): Query;

    public function createProjection(
        string $name,
        array $options = []
    ): Projector;

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        array $options = []
    ): ReadModelProjector;

    /**
     * @throws ProjectionNotFound
     */
    public function deleteProjection(string $name, bool $deleteEmittedEvents): void;

    /**
     * @throws ProjectionNotFound
     */
    public function resetProjection(string $name): void;

    /**
     * @throws ProjectionNotFound
     */
    public function stopProjection(string $name): void;

    /**
     * @return string[]
     */
    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array;

    /**
     * @return string[]
     */
    public function fetchProjectionNamesRegex(string $regex, int $limit = 20, int $offset = 0): array;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionStatus(string $name): ProjectionStatus;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionStreamPositions(string $name): array;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionState(string $name): array;
}
