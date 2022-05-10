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

use Prooph\EventStore\SubscriptionDropReason;
use Throwable;

/** @internal */
class DropData
{
    private SubscriptionDropReason $reason;

    private ?Throwable $error;

    public function __construct(SubscriptionDropReason $reason, ?Throwable $error)
    {
        $this->reason = $reason;
        $this->error = $error;
    }

    public function reason(): SubscriptionDropReason
    {
        return $this->reason;
    }

    public function error(): ?Throwable
    {
        return $this->error;
    }
}
