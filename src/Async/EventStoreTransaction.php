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

namespace Prooph\EventStore\Async;

use Amp\Promise;
use Prooph\EventStore\Async\Internal\EventStoreTransactionConnection;
use Prooph\EventStore\EventData;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStore\WriteResult;

class EventStoreTransaction
{
    private int $transactionId;
    private ?UserCredentials $userCredentials;
    private EventStoreTransactionConnection $connection;
    private bool $isRolledBack;
    private bool $isCommitted;

    public function __construct(
        int $transactionId,
        ?UserCredentials $userCredentials,
        EventStoreTransactionConnection $connection
    ) {
        $this->transactionId = $transactionId;
        $this->userCredentials = $userCredentials;
        $this->connection = $connection;
        $this->isRolledBack = false;
        $this->isCommitted = false;
    }

    public function transactionId(): int
    {
        return $this->transactionId;
    }

    /** @return Promise<WriteResult> */
    public function commitAsync(): Promise
    {
        if ($this->isRolledBack) {
            throw new \RuntimeException('Cannot commit a rolledback transaction');
        }

        if ($this->isCommitted) {
            throw new \RuntimeException('Transaction is already committed');
        }

        return $this->connection->commitTransactionAsync($this, $this->userCredentials);
    }

    /**
     * @param EventData[] $events
     *
     * @return Promise<void>
     */
    public function writeAsync(array $events = []): Promise
    {
        if ($this->isRolledBack) {
            throw new \RuntimeException('Cannot commit a rolledback transaction');
        }

        if ($this->isCommitted) {
            throw new \RuntimeException('Transaction is already committed');
        }

        return $this->connection->transactionalWriteAsync($this, $events, $this->userCredentials);
    }

    public function rollback(): void
    {
        if ($this->isCommitted) {
            throw new \RuntimeException('Transaction is already committed');
        }

        $this->isRolledBack = true;
    }
}
