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

namespace Prooph\EventStore\Common;

enum SystemConsumerStrategy
{
    // Distributes events to a single client until it is full. Then round robin to the next client.
    case DispatchToSingle;

    // Distribute events to each client in a round robin fashion.
    case RoundRobin;

    // Distribute events of the same streamId to the same client until it disconnects on a best efforts basis.
    // Designed to be used with indexes such as the category projection.
    case Pinned;
}
