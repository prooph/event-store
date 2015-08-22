<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 30.08.14 - 22:48
 */

namespace Prooph\EventStore\Exception;

class StreamNotFoundException extends \RuntimeException implements EventStoreException
{
}
