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

interface Projection
{
    /**
     * The callback has to return an array
     */
    public function init(Closure $callback): Projection;

    public function fromStream(string $streamName): Projection;

    public function fromStreams(string ...$streamNames): Projection;

    public function fromCategory(string $name): Projection;

    public function fromCategories(string ...$names): Projection;

    public function fromAll(): Projection;

    /**
     * For example:
     *
     * when([
     *     'UserCreated' => function (array $state, Message $event) {
     *         $state['count']++;
     *         return $state;
     *     },
     *     'UserDeleted' => function (array $state, Message $event) {
     *         $state['count']--;
     *         return $state;
     *     }
     * ])
     */
    public function when(array $handlers): Projection;

    /**
     * For example:
     * function(array $state, Message $event) {
     *     $state['count']++;
     *     return $state;
     * }
     */
    public function whenAny(Closure $closure): Projection;

    public function reset(): void;

    public function stop(): void;

    public function getState(): array;

    public function getName(): string;

    public function emit(Message $event): void;

    public function linkTo(string $streamName, Message $event): void;

    public function delete(bool $deleteEmittedEvents): void;

    public function run(bool $keepRunning = true): void;
}
