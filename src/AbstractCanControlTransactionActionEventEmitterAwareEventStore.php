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

use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotCommitted;
use Prooph\EventStore\Exception\TransactionNotStarted;

abstract class AbstractCanControlTransactionActionEventEmitterAwareEventStore
    extends AbstractActionEventEmitterAwareEventStore implements
    CanControlTransactionActionEventEmitterAware
{
    protected $isInTransaction = false;

    public function beginTransaction(): void
    {
        if ($this->isInTransaction) {
            throw new TransactionAlreadyStarted();
        }

        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_BEGIN_TRANSACTION,
            $this,
            ['inTransaction' => true]
        );
        $this->getActionEventEmitter()->dispatch($event);

        $result = $event->getParam('inTransaction', false);

        if (! $result) {
            throw new TransactionAlreadyStarted();
        }

        $this->isInTransaction = true;
    }

    public function commit(): void
    {
        if (! $this->isInTransaction) {
            throw new TransactionNotStarted();
        }

        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_COMMIT,
            $this,
            ['inTransaction' => true]
        );
        $this->getActionEventEmitter()->dispatch($event);

        $result = $event->getParam('inTransaction', true);

        if ($result) {
            throw new TransactionNotCommitted();
        }

        $this->isInTransaction = false;
    }

    public function rollback(): void
    {
        if (! $this->isInTransaction) {
            throw new TransactionNotStarted();
        }

        $event = $this->actionEventEmitter->getNewActionEvent(
            self::EVENT_ROLLBACK,
            $this,
            ['inTransaction' => true]
        );
        $this->getActionEventEmitter()->dispatch($event);

        $result = $event->getParam('inTransaction', true);

        if ($result) {
            throw new TransactionNotCommitted();
        }

        $this->isInTransaction = false;
    }

    public function isInTransaction(): bool
    {
        return $this->isInTransaction;
    }
}
