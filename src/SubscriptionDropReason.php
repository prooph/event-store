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

namespace Prooph\EventStore;

enum SubscriptionDropReason: int
{
    case UserInitiated = 0;
    case NotAuthenticated = 1;
    case AccessDenied = 2;
    case SubscribingError = 3;
    case ServerError = 4;
    case ConnectionClosed = 5;
    case CatchUpError = 6;
    case ProcessingQueueOverflow = 7;
    case EventHandlerException = 8;
    case MaxSubscribersReached = 9;
    case PersistentSubscriptionDeleted = 10;
    case Unknown = 100;
    case NotFound = 11;
}
