<?php
/**
 * This file is part of the prooph/service-bus.
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
 * Interface DefaultAggregateRootContract
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface DefaultAggregateRootContract
{
    public static function reconstituteFromHistory(\Iterator $historyEvents) : DefaultAggregateRootContract;

    /**
     * @return int
     */
    public function getVersion() : int;

    /**
     * @return string
     */
    public function getId() : string;

    /**
     * @return Message[]
     */
    public function popRecordedEvents() : \Iterator;

    /**
     * @param $event
     */
    public function replay($event) : void;
}
