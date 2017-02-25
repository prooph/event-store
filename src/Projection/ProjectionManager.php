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

namespace Prooph\EventStore\Projection;

interface ProjectionManager
{
    public function createQuery(): Query;

    public function createProjection(
        string $name,
        array $options = []
    ): Projection;

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        array $options = []
    ): ReadModelProjection;

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void;

    public function resetProjection(string $name): void;

    public function stopProjection(string $name): void;

    /**
     * @return string[]
     */
    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array;

    /**
     * @return string[]
     */
    public function fetchProjectionNamesRegex(string $regex, int $limit = 20, int $offset = 0): array;

    public function fetchProjectionStatus(string $name): ProjectionStatus;

    public function fetchProjectionStreamPositions(string $name): array;

    public function fetchProjectionState(string $name): array;
}
