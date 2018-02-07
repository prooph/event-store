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

use Closure;

interface Query
{
    /**
     * The callback has to return an array
     */
    public function init(Closure $callback): Query;

    public function fromStream(string $streamName): Query;

    public function fromStreams(string ...$streamNames): Query;

    public function fromCategory(string $name): Query;

    public function fromCategories(string ...$names): Query;

    public function fromAll(): Query;

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
    public function when(array $handlers): Query;

    /**
     * For example:
     * function(array $state, Message $event) {
     *     $state->count++;
     *     return $state;
     * }
     */
    public function whenAny(Closure $closure): Query;

    public function reset(): void;

    public function run(): void;

    public function stop(): void;

    public function getState(): array;
}
