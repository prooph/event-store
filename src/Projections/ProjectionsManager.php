<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projections;

use Prooph\EventStore\UserCredentials;

interface ProjectionsManager
{
    /**
     * Enables a projection
     */
    public function enable(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Aborts and disables a projection without writing a checkpoint
     */
    public function disable(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Disables a projection
     */
    public function abort(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Creates a one-time query
     */
    public function createOneTime(
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Creates a one-time query
     */
    public function createTransient(
        string $name,
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Creates a continuous projection
     */
    public function createContinuous(
        string $name,
        string $query,
        bool $trackEmittedStreams = false,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Lists all projections
     *
     * @return list<ProjectionDetails>
     */
    public function listAll(?UserCredentials $userCredentials = null): array;

    /**
     * Lists all one-time projections
     *
     * @return list<ProjectionDetails>
     */
    public function listOneTime(?UserCredentials $userCredentials = null): array;

    /**
     * Lists this status of all continuous projections
     *
     * @return list<ProjectionDetails>
     */
    public function listContinuous(?UserCredentials $userCredentials = null): array;

    /**
     * Gets the status of a projection
     */
    public function getStatus(string $name, ?UserCredentials $userCredentials = null): ProjectionDetails;

    /**
     * Gets the state of a projection.
     */
    public function getState(string $name, ?UserCredentials $userCredentials = null): State;

    /**
     * Gets the state of a projection for a specified partition
     */
    public function getPartitionState(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): State;

    /**
     * Gets the result of a projection
     */
    public function getResult(string $name, ?UserCredentials $userCredentials = null): State;

    /**
     * Gets the result of a projection for a specified partition
     */
    public function getPartitionResult(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): State;

    /**
     * Gets the statistics of a projection
     */
    public function getStatistics(string $name, ?UserCredentials $userCredentials = null): ProjectionStatistics;

    /**
     * Gets the status of a query
     */
    public function getQuery(string $name, ?UserCredentials $userCredentials = null): Query;

    /**
     * Updates the definition of a query
     */
    public function updateQuery(
        string $name,
        string $query,
        ?bool $emitEnabled = null,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Deletes a projection
     */
    public function delete(
        string $name,
        bool $deleteEmittedStreams = false,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Resets a projection
     */
    public function reset(string $name, ?UserCredentials $userCredentials = null): void;
}
