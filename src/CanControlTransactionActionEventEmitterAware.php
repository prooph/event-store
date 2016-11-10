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

interface CanControlTransactionActionEventEmitterAware extends CanControlTransaction, ActionEventEmitterAware
{
    const EVENT_BEGIN_TRANSACTION = 'beginTransaction';
    const EVENT_COMMIT = 'commit';
    const EVENT_IS_IN_TRANSACTION = 'isInTransaction';
    const EVENT_ROLLBACK = 'rollback';
}
