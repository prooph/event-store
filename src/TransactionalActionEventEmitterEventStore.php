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

namespace Prooph\EventStore;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotStarted;

class TransactionalActionEventEmitterEventStore extends ActionEventEmitterEventStore implements TransactionalEventStore
{
    public const EVENT_BEGIN_TRANSACTION = 'beginTransaction';
    public const EVENT_COMMIT = 'commit';
    public const EVENT_ROLLBACK = 'rollback';

    public const ALL_EVENTS = [
        self::EVENT_APPEND_TO,
        self::EVENT_CREATE,
        self::EVENT_LOAD,
        self::EVENT_LOAD_REVERSE,
        self::EVENT_DELETE,
        self::EVENT_HAS_STREAM,
        self::EVENT_FETCH_STREAM_METADATA,
        self::EVENT_UPDATE_STREAM_METADATA,
        self::EVENT_FETCH_STREAM_NAMES,
        self::EVENT_FETCH_STREAM_NAMES_REGEX,
        self::EVENT_FETCH_CATEGORY_NAMES,
        self::EVENT_FETCH_CATEGORY_NAMES_REGEX,
        self::EVENT_BEGIN_TRANSACTION,
        self::EVENT_COMMIT,
        self::EVENT_ROLLBACK,
    ];

    /**
     * @var TransactionalEventStore
     */
    protected $eventStore;

    public function __construct(TransactionalEventStore $eventStore, ActionEventEmitter $actionEventEmitter)
    {
        parent::__construct($eventStore, $actionEventEmitter);

        $actionEventEmitter->attachListener(self::EVENT_BEGIN_TRANSACTION, function (ActionEvent $event): void {
            try {
                $this->eventStore->beginTransaction();
            } catch (TransactionAlreadyStarted $exception) {
                $event->setParam('transactionAlreadyStarted', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_COMMIT, function (ActionEvent $event): void {
            try {
                $this->eventStore->commit();
            } catch (TransactionNotStarted $exception) {
                $event->setParam('transactionNotStarted', true);
            }
        });

        $actionEventEmitter->attachListener(self::EVENT_ROLLBACK, function (ActionEvent $event): void {
            try {
                $this->eventStore->rollback();
            } catch (TransactionNotStarted $exception) {
                $event->setParam('transactionNotStarted', true);
            }
        });
    }

    public function beginTransaction(): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_BEGIN_TRANSACTION, $this);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('transactionAlreadyStarted', false)) {
            throw new TransactionAlreadyStarted();
        }
    }

    public function commit(): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_COMMIT, $this);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('transactionNotStarted', false)) {
            throw new TransactionNotStarted();
        }
    }

    public function rollback(): void
    {
        $event = $this->actionEventEmitter->getNewActionEvent(self::EVENT_ROLLBACK, $this);

        $this->actionEventEmitter->dispatch($event);

        if ($event->getParam('transactionNotStarted', false)) {
            throw new TransactionNotStarted();
        }
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transactional(callable $callable)
    {
        return $this->eventStore->transactional($callable);
    }

    public function inTransaction(): bool
    {
        return $this->eventStore->inTransaction();
    }
}
