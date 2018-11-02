<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Util;

use Prooph\EventStore\Exception\InvalidArgumentException;

class Assertion extends \Assert\Assertion
{
    /**
     * Exception to throw when an assertion failed.
     *
     * @var string
     */
    protected static $exceptionClass = InvalidArgumentException::class;

    protected static function createException(
        $value,
        $message,
        $code,
        $propertyPath = null,
        array $constraints = []
    ): InvalidArgumentException {
        $exceptionClass = static::$exceptionClass;

        return new $exceptionClass($message, $code);
    }
}
