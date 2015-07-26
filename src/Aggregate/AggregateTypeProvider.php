<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.05.14 - 20:17
 */

namespace Prooph\EventStore\Aggregate;

/**
 * Interface AggregateTypeProvider
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface AggregateTypeProvider
{
    /**
     * @return AggregateType
     */
    public function aggregateType();
}
 