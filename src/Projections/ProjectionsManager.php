<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projections;

use Amp\Promise;
use Prooph\EventStore\UserCredentials;

interface ProjectionsManager
{
    /**
     * Asynchronously enables a projection
     */
    public function enable(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously aborts and disables a projection without writing a checkpoint
     */
    public function disable(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously disables a projection
     */
    public function abort(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously creates a one-time query
     */
    public function createOneTime(
        string $query,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously creates a one-time query
     */
    public function createTransient(
        string $name,
        string $query,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously creates a continuous projection
     */
    public function createContinuous(
        string $name,
        string $query,
        bool $trackEmittedStreams = false,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously lists all projections
     *
     * @return Promise<ProjectionDetails[]>
     */
    public function listAll(?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously lists all one-time projections
     *
     * @return Promise<ProjectionDetails[]>
     */
    public function listOneTime(?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously lists this status of all continuous projections
     *
     * @return Promise<ProjectionDetails[]>
     */
    public function listContinuous(?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously gets the status of a projection
     *
     * returns String of JSON containing projection status
     *
     * @return Promise<string>
     */
    public function getStatus(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously gets the state of a projection.
     *
     * returns String of JSON containing projection state
     *
     * @return Promise<string>
     */
    public function getState(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously gets the state of a projection for a specified partition
     *
     * returns String of JSON containing projection state
     *
     * @return Promise<string>
     */
    public function getPartitionState(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously gets the resut of a projection
     *
     * returns String of JSON containing projection result
     *
     * @return Promise<string>
     */
    public function getResult(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously gets the result of a projection for a specified partition
     *
     * returns String of JSON containing projection result
     *
     * @return Promise<string>
     */
    public function getPartitionResult(
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously gets the statistics of a projection
     *
     * returns String of JSON containing projection statistics
     *
     * @return Promise<string>
     */
    public function getStatistics(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously gets the status of a query
     *
     * @return Promise<string>
     */
    public function getQuery(string $name, ?UserCredentials $userCredentials = null): void;

    /**
     * Asynchronously updates the definition of a query
     */
    public function updateQuery(
        string $name,
        string $query,
        bool $emitEnabled = false,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously deletes a projection
     */
    public function delete(
        string $name,
        bool $deleteEmittedStreams = false,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * Asynchronously resets a projection
     */
    public function reset(string $name, ?UserCredentials $userCredentials = null): void;
}
