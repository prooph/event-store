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

use Closure;
use Prooph\Common\Messaging\Message;

interface ReadModelProjection
{
    /**
     * The callback has to return an array
     */
    public function init(Closure $callback): ReadModelProjection;

    public function fromStream(string $streamName): ReadModelProjection;

    public function fromStreams(string ...$streamNames): ReadModelProjection;

    public function fromCategory(string $name): ReadModelProjection;

    public function fromCategories(string ...$names): ReadModelProjection;

    public function fromAll(): ReadModelProjection;

    /**
     * For example:
     *
     * when([
     *     'UserCreated' => function (array $state, Message $event) {
     *         $state->count++;
     *         return $state;
     *     },
     *     'UserDeleted' => function (array $state, Message $event) {
     *         $state->count--;
     *         return $state;
     *     }
     * ])
     */
    public function when(array $handlers): ReadModelProjection;

    /**
     * For example:
     * function(array $state, Message $event) {
     *     $state->count++;
     *     return $state;
     * }
     */
    public function whenAny(Closure $closure): ReadModelProjection;

    public function reset(): void;

    public function stop(): void;

    public function getState(): array;

    public function getName(): string;

    public function delete(bool $deleteProjection): void;

    public function run(bool $keepRunning = true): void;

    public function readModel(): ReadModel;
}
