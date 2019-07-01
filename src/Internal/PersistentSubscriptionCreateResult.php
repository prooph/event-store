<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Internal;

class PersistentSubscriptionCreateResult
{
    /** @var PersistentSubscriptionCreateStatus */
    private $status;

    /** @internal */
    public function __construct(PersistentSubscriptionCreateStatus $status)
    {
        $this->status = $status;
    }

    public function status(): PersistentSubscriptionCreateStatus
    {
        return $this->status;
    }
}
