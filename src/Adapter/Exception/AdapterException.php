<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 04/17/14 - 8:40 PM
 */

namespace Prooph\EventStore\Adapter\Exception;

use Prooph\EventStore\Exception\EventStoreException;

/**
 * Marker interface AdapterException
 *
 * @package Prooph\EventStore\Adapter\Exception
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface AdapterException extends EventStoreException
{
}
