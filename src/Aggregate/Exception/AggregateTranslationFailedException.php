<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 31.08.14 - 01:29
 */

namespace Prooph\EventStore\Aggregate\Exception;

use Prooph\EventStore\Exception\EventStoreException;

class AggregateTranslationFailedException extends \RuntimeException implements EventStoreException
{
}
