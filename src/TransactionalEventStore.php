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

namespace Prooph\EventStore;

/**
 * This interfaces describes that an event store implementation allows control of the transaction handling
 */
interface TransactionalEventStore extends EventStore
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function isInTransaction(): bool;

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transactional(callable $callable);
}
