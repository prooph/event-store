<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Plugin;

use Iterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Upcasting\Upcaster;
use Prooph\EventStore\Upcasting\UpcastingIterator;

final class UpcastingPlugin extends AbstractPlugin
{
    public const ACTION_EVENT_PRIORITY = -1000;

    /**
     * @var Upcaster
     */
    private $upcaster;

    public function __construct(Upcaster $upcaster)
    {
        $this->upcaster = $upcaster;
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $upcaster = function (ActionEvent $actionEvent): void {
            $streamEvents = $actionEvent->getParam('streamEvents');

            if (! $streamEvents instanceof Iterator) {
                return;
            }

            $actionEvent->setParam('streamEvents', new UpcastingIterator($this->upcaster, $streamEvents));
        };

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_LOAD,
            $upcaster,
            self::ACTION_EVENT_PRIORITY
        );

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_LOAD_REVERSE,
            $upcaster,
            self::ACTION_EVENT_PRIORITY
        );
    }
}
