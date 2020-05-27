<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Async\Projections;

use Amp\Promise;
use Prooph\EventStore\Projections\ProjectionDetails;
use Prooph\EventStore\Projections\ProjectionStatistics;
use Prooph\EventStore\Projections\Query;
use Prooph\EventStore\Projections\State;
use Prooph\EventStore\UserCredentials;

interface ProjectionsManager
{
    /**
     * Asynchronously enables a projection
     *
     * @return Promise<void>
     */
    public function enableAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously aborts and disables a projection without writing a checkpoint
     *
     * @return Promise<void>
     */
    public function disableAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously disables a projection
     *
     * @return Promise<void>
     */
    public function abortAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously creates a one-time query
     *
     * @return Promise<void>
     */
    public function createOneTimeAsync(
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously creates a one-time query
     *
     * @return Promise<void>
     */
    public function createTransientAsync(
        string $name,
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously creates a continuous projection
     *
     * @return Promise<void>
     */
    public function createContinuousAsync(
        string $name,
        string $query,
        bool $trackEmittedStreams = false,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously lists all projections
     *
     * @return Promise<list<ProjectionDetails>>
     */
    public function listAllAsync(?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously lists all one-time projections
     *
     * @return Promise<list<ProjectionDetails>>
     */
    public function listOneTimeAsync(?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously lists this status of all continuous projections
     *
     * @return Promise<list<ProjectionDetails>>
     */
    public function listContinuousAsync(?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the status of a projection
     *
     * @return Promise<ProjectionDetails>
     */
    public function getStatusAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the state of a projection.
     *
     * @return Promise<State>
     */
    public function getStateAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the state of a projection for a specified partition
     *
     * @return Promise<State>
     */
    public function getPartitionStateAsync(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously gets the result of a projection
     *
     * @return Promise<State>
     */
    public function getResultAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the result of a projection for a specified partition
     *
     * @return Promise<State>
     */
    public function getPartitionResultAsync(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously gets the statistics of a projection
     *
     * @return Promise<ProjectionStatistics>
     */
    public function getStatisticsAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the status of a query
     *
     * @return Promise<Query>
     */
    public function getQueryAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously updates the definition of a query
     *
     * @return Promise<void>
     */
    public function updateQueryAsync(
        string $name,
        string $query,
        ?bool $emitEnabled = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously deletes a projection
     *
     * @return Promise<void>
     */
    public function deleteAsync(
        string $name,
        bool $deleteEmittedStreams = false,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously resets a projection
     *
     * @return Promise<void>
     */
    public function resetAsync(string $name, ?UserCredentials $userCredentials = null): Promise;
}
