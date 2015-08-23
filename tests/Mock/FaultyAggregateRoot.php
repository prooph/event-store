<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/22/15 - 11:06 PM
 */
namespace Prooph\EventStoreTest\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Class FaultyAggregateRoot
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class FaultyAggregateRoot implements DefaultAggregateRootContract
{

    /**
     * @param Message[] $historyEvents
     * @return DefaultAggregateRootContract
     */
    public static function reconstituteFromHistory($historyEvents)
    {
        //faulty method
        return;
    }

    /**
     * @return string
     */
    public function getId()
    {
        //faulty method
        return;
    }

    /**
     * @return Message[]
     */
    public function popRecordedEvents()
    {
        //faulty method
        return;
    }
}
