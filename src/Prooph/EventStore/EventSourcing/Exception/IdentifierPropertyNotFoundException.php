<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 23:35
 */

namespace Prooph\EventStore\EventSourcing\Exception;

use Prooph\EventStore\Exception\EventStoreException;

/**
 * Class IdentifierPropertyNotFoundException
 *
 * @package Prooph\EventStore\EventSourcing\Exception
 * @author Alexander Miertsch <contact@prooph.de>
 */
class IdentifierPropertyNotFoundException extends \RuntimeException implements EventStoreException
{
}
 