<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Common;

class SystemEventTypes
{
    // event type for stream deleted
    public const STREAM_DELETED = '$streamDeleted';
    // event type for statistics
    public const STATS_COLLECTED = '$statsCollected';
    // event type for linkTo
    public const LINK_TO = '$>';
    // event type for stream metadata
    public const STREAM_METADATA = '$metadata';
    // event type for the system settings
    public const SETTINGS = '$settings';
}
