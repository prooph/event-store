<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Amp\Promise;
use Prooph\EventStore\Async\Internal\EventStoreTransactionConnection;

class AsyncEventStoreTransaction
{
    /** @var int */
    private $transactionId;
    /** @var UserCredentials|null */
    private $userCredentials;
    /** @var EventStoreTransactionConnection */
    private $connection;
    /** @var bool */
    private $isRolledBack;
    /** @var bool */
    private $isCommitted;

    public function __construct(
        int $transactionId,
        ?UserCredentials $userCredentials,
        EventStoreTransactionConnection $connection
    ) {
        $this->transactionId = $transactionId;
        $this->userCredentials = $userCredentials;
        $this->connection = $connection;
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
