<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 05/24/14 - 08:17 AM
 */

namespace Prooph\EventStore\Aggregate;

/**
 * Interface AggregateTypeProvider
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface AggregateTypeProvider
{
    /**
     * @return AggregateType
     */
    public function aggregateType();
}
