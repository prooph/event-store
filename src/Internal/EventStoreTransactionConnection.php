<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Internal;

use Prooph\EventStore\EventStoreTransaction;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStore\WriteResult;

/** @internal */
interface EventStoreTransactionConnection
{
    public function transactionalWrite(
        EventStoreTransaction $transaction,
        array $events,
        ?UserCredentials $userCredentials
    ): void;

    public function commitTransaction(
        EventStoreTransaction $transaction,
        ?UserCredentials $userCredentials
    ): WriteResult;
}
