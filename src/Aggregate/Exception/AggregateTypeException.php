<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 01:30
 */

namespace Prooph\EventStore\Aggregate\Exception;

use Prooph\EventStore\Exception\EventStoreException;

class AggregateTypeException extends \InvalidArgumentException implements EventStoreException
{

}
 