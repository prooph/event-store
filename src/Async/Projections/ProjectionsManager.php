<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Async\Projections;

use Amp\Promise;
use Prooph\EventStore\Projections\ProjectionDetails;
use Prooph\EventStore\UserCredentials;

interface ProjectionsManager
{
    /**
     * Asynchronously enables a projection
     */
    public function enableAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously aborts and disables a projection without writing a checkpoint
     */
    public function disableAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously disables a projection
     */
    public function abortAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously creates a one-time query
     */
    public function createOneTimeAsync(
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously creates a one-time query
     */
    public function createTransientAsync(
        string $name,
        string $query,
        string $type = 'JS',
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously creates a continuous projection
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
     * @return Promise<ProjectionDetails[]>
     */
    public function listAllAsync(?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously lists all one-time projections
     *
     * @return Promise<ProjectionDetails[]>
     */
    public function listOneTimeAsync(?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously lists this status of all continuous projections
     *
     * @return Promise<ProjectionDetails[]>
     */
    public function listContinuousAsync(?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the status of a projection
     *
     * returns String of JSON containing projection status
     *
     * @return Promise<string>
     */
    public function getStatusAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the state of a projection.
     *
     * returns String of JSON containing projection state
     *
     * @return Promise<string>
     */
    public function getStateAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the state of a projection for a specified partition
     *
     * returns String of JSON containing projection state
     *
     * @return Promise<string>
     */
    public function getPartitionStateAsync(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously gets the resut of a projection
     *
     * returns String of JSON containing projection result
     *
     * @return Promise<string>
     */
    public function getResultAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the result of a projection for a specified partition
     *
     * returns String of JSON containing projection result
     *
     * @return Promise<string>
     */
    public function getPartitionResultAsync(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously gets the statistics of a projection
     *
     * returns String of JSON containing projection statistics
     *
     * @return Promise<string>
     */
    public function getStatisticsAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously gets the status of a query
     *
     * @return Promise<string>
     */
    public function getQueryAsync(string $name, ?UserCredentials $userCredentials = null): Promise;

    /**
     * Asynchronously updates the definition of a query
     */
    public function updateQueryAsync(
        string $name,
        string $query,
        ?bool $emitEnabled = null,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously deletes a projection
     */
    public function deleteAsync(
        string $name,
        bool $deleteEmittedStreams = false,
        ?UserCredentials $userCredentials = null
    ): Promise;

    /**
     * Asynchronously resets a projection
     */
    public function resetAsync(string $name, ?UserCredentials $userCredentials = null): Promise;
}
