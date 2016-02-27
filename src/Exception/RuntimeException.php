<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 04/19/14 - 08:42 PM
 */

namespace Prooph\EventStore\Exception;

/**
 * Class RuntimeException
 *
 * @package Prooph\EventStore\Exception
 * @author Alexander Miertsch <contact@prooph.de>
 */
class RuntimeException extends \RuntimeException implements EventStoreException
{
}
