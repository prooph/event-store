<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Adapter\Exception;

/**
 * Class InvalidArgumentException
 *
 * @package Prooph\EventStore\Adapter\Exception
 * @author Alexander Miertsch <contact@prooph.de>
 */
class InvalidArgumentException extends \InvalidArgumentException implements AdapterException
{
}
