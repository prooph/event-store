<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamProjection;

use Closure;
use stdClass;

interface Query
{
    /**
     * The callback has to return an instance of stdClass
     */
    public function init(callable $callback): Query;

    public function fromStream(string $streamName): Query;

    public function fromStreams(string ...$streamNames): Query;

    public function fromCategory(string $name): Query;

    public function fromCategories(string ...$names): Query;

    public function fromAll(): Query;

    /**
     * For example:
     *
     * when([
     *     'UserCreated' => function (stdClass $state, Message $event) {
     *         $state->count++;
     *     },
     *     'UserDeleted' => function (stdClass $state, Message $event) {
     *         $state->count--;
     *     }
     * ])
     */
    public function when(array $handlers): Query;

    /**
     * For example:
     * function(stdClass $state, Message $event) {
     *     $state->count++;
     *     return $state;
     * }
     */
    public function whenAny(Closure $closure): Query;

    public function reset(): void;

    public function run(): void;

    public function getState(): stdClass;
}
