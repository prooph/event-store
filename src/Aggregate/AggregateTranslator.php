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

namespace Prooph\EventStore\Aggregate;

use Iterator;
use Prooph\Common\Messaging\Message;

/**
 * Interface AggregateTranslator
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface AggregateTranslator
{
    public function extractAggregateVersion(object $eventSourcedAggregateRoot) : int;

    public function extractAggregateId(object $eventSourcedAggregateRoot) : string;

    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, Iterator $historyEvents) : object;

    /**
     * @param object $eventSourcedAggregateRoot
     * @return Message[]
     */
    public function extractPendingStreamEvents(object $eventSourcedAggregateRoot) : array;

    public function replayStreamEvents(object $eventSourcedAggregateRoot, Iterator $events) : void;
}
