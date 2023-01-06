<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2023 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2023 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use MabeEnum\Enum;

/**
 * @method static ProjectionStatus RUNNING
 * @method static ProjectionStatus STOPPING
 * @method static ProjectionStatus DELETING
 * @method static ProjectionStatus DELETING_INCL_EMITTED_EVENTS
 * @method static ProjectionStatus RESETTING
 * @method static ProjectionStatus IDLE
 */
final class ProjectionStatus extends Enum
{
    public const RUNNING = 'running';
    public const STOPPING = 'stopping';
    public const DELETING = 'deleting';
    public const DELETING_INCL_EMITTED_EVENTS = 'deleting incl emitted events';
    public const RESETTING = 'resetting';
    public const IDLE = 'idle';
}
