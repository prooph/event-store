<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Internal;

use Amp\Promise;
use Prooph\EventStore\AsyncEventStoreTransaction;
use Prooph\EventStore\UserCredentials;

/** @internal */
interface EventStoreTransactionConnection
{
    public function transactionalWriteAsync(
        AsyncEventStoreTransaction $transaction,
        array $events,
        ?UserCredentials $userCredentials
    ): Promise;

    /** @return Promise<WriteResult> */
    public function commitTransactionAsync(
        AsyncEventStoreTransaction $transaction,
        ?UserCredentials $userCredentials
    ): Promise;
}
