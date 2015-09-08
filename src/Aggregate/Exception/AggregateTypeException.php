<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/31/14 - 01:30 AM
 */

namespace Prooph\EventStore\Aggregate\Exception;

use Prooph\EventStore\Exception\EventStoreException;

/**
 * Class AggregateTypeException
 * @package Prooph\EventStore\Aggregate\Exception
 */
class AggregateTypeException extends InvalidArgumentException implements EventStoreException
{
}
