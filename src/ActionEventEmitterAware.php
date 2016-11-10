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

use Prooph\Common\Event\ActionEventEmitter;

interface ActionEventEmitterAware
{
    const EVENT_APPEND_TO = 'appendTo';
    const EVENT_CREATE = 'create';
    const EVENT_LOAD = 'load';
    const EVENT_LOAD_EVENTS = 'loadEvents';
    const EVENT_LOAD_REVERSE = 'loadReverse';

    public function getActionEventEmitter(): ActionEventEmitter;
}
