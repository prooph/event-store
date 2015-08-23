<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/22/15 - 10:08 PM
 */

namespace Prooph\EventStoreTest\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Interface CustomAggregateRootContract
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
 */
interface CustomAggregateRootContract
{
    /**
     * @param Message[] $historyEvents
     * @return CustomAggregateRootContract
     */
    public static function buildFromHistoryEvents($historyEvents);

    /**
     * @return string
     */
    public function identifier();

    /**
     * @return Message[]
     */
    public function getPendingEvents();
}
