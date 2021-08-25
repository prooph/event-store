<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
     * Synchronously enables a projection
     */
    public function enable(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Synchronously aborts and disables a projection without writing a checkpoint
     */
    public function disable(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Synchronously disables a projection
     */
    public function abort(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Synchronously creates a one-time query
     */
    public function createOneTime(
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Synchronously creates a one-time query
     */
    public function createTransient(
        string $name,
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Synchronously creates a continuous projection
     */
    public function createContinuous(
        string $name,
        string $query,
        bool $trackEmittedStreams = false,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Synchronously lists all projections
     *
     * @return list<ProjectionDetails>
     */
    public function listAll(?UserCredentials $userCredentials = null): array;

    /**
     * Synchronously lists all one-time projections
     *
     * @return list<ProjectionDetails>
     */
    public function listOneTime(?UserCredentials $userCredentials = null): array;

    /**
     * Synchronously lists this status of all continuous projections
     *
     * @return list<ProjectionDetails>
     */
    public function listContinuous(?UserCredentials $userCredentials = null): array;

    /**
     * Synchronously gets the status of a projection
     *
     * @return ProjectionDetails
     */
    public function getStatus(string $name, ?UserCredentials $userCredentials = null): ProjectionDetails;

    /**
     * Synchronously gets the state of a projection.
     *
     * @return State
     */
    public function getState(string $name, ?UserCredentials $userCredentials = null): State;

    /**
     * Synchronously gets the state of a projection for a specified partition
     *
     * @return State
     */
    public function getPartitionState(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): State;

    /**
     * Synchronously gets the resut of a projection
     *
     * @return State
     */
    public function getResult(string $name, ?UserCredentials $userCredentials = null): State;

    /**
     * Synchronously gets the result of a projection for a specified partition
     *
     * @return State
     */
    public function getPartitionResult(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): State;

    /**
     * Synchronously gets the statistics of a projection
     *
     * @return ProjectionStatistics
     */
    public function getStatistics(string $name, ?UserCredentials $userCredentials = null): ProjectionStatistics;

    /**
     * Synchronously gets the status of a query
     *
     * @return Query
     */
    public function getQuery(string $name, ?UserCredentials $userCredentials = null): Query;

    /**
     * Synchronously updates the definition of a query
     */
    public function updateQuery(
        string $name,
        string $query,
        ?bool $emitEnabled = null,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Synchronously deletes a projection
     */
    public function delete(
        string $name,
        bool $deleteEmittedStreams = false,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Synchronously resets a projection
     */
    public function reset(string $name, ?UserCredentials $userCredentials = null): void;
}
