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

enum SystemEventTypes: string
{
    // event type for stream deleted
    case StreamDeleted = '$streamDeleted';

    // event type for statistics
    case StatsCollected = '$statsCollected';

    // event type for linkTo
    case LinkTo = '$>';

    // event type for stream metadata
    case StreamMetadata = '$metadata';

    // event type for the system settings
    case Settings = '$settings';
}
