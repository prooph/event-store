<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 19.04.14 - 20:42
 */

namespace Prooph\EventStore\Exception;

/**
 * Class RuntimeException
 *
 * @package Prooph\EventStore\Exception
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RuntimeException extends \RuntimeException implements EventStoreException
{
}
