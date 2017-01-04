<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\Message;

/**
 * Interface CustomAggregateRootContract
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface CustomAggregateRootContract
{
    /**
     * @return int
     */
    public function version();

    /**
     * @param \Iterator $historyEvents
     * @return CustomAggregateRootContract
     */
    public static function buildFromHistoryEvents(\Iterator $historyEvents);

    /**
     * @return string
     */
    public function identifier();

    /**
     * @return Message[]
     */
    public function getPendingEvents();
}
