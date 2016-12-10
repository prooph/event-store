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

interface TransactionalActionEventEmitterEventStore extends
    ActionEventEmitterEventStore,
    TransactionalEventStore
{
    public const EVENT_BEGIN_TRANSACTION = 'beginTransaction';
    public const EVENT_COMMIT = 'commit';
    public const EVENT_ROLLBACK = 'rollback';
}
