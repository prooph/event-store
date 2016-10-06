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

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Class FaultyAggregateRoot
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
final class FaultyAggregateRoot implements DefaultAggregateRootContract
{
    public function getVersion(): int
    {
        //faulty return
        return 1;
    }

    /**
     * @param \Iterator $historyEvents
     * @return \stdClass
     */
    public static function reconstituteFromHistory(\Iterator $historyEvents): DefaultAggregateRootContract
    {
        //faulty method
        return new class implements DefaultAggregateRootContract {
            public static function reconstituteFromHistory(\Iterator $historyEvents): DefaultAggregateRootContract
            {
                return new self();
            }

            public function getVersion(): int
            {
                return 1;
            }

            public function getId(): string
            {
                return 'id';
            }

            /**
             * @return Message[]
             */
            public function popRecordedEvents(): array
            {
                return [];
            }

            /**
             * @param $event
             */
            public function replay($event): void
            {
            }
        };
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        //faulty method
        return '0';
    }

    /**
     * @return Message[]
     */
    public function popRecordedEvents(): array
    {
        //faulty method
        return [];
    }

    /**
     * @param $event
     */
    public function replay($event): void
    {
    }
}
