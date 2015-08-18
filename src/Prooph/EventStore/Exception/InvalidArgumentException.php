<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 19.04.14 - 22:51
 */

namespace Prooph\EventStore\Exception;

/**
 * Class InvalidArgumentException
 *
 * @package Prooph\EventStore\Exception
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class InvalidArgumentException extends \InvalidArgumentException implements EventStoreException
{
}
