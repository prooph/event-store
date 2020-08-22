<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Internal;

/**
 * @internal
 * To work around https://github.com/EventStore/EventStore/issues/1903
 */
final class DateTimeStringBugWorkaround
{
    public static function fixDateTimeString(string $dateTimeString): string
    {
        $micros = \substr($dateTimeString, 20, -1);

        if (false === $micros) {
            // no microseconds given
            $dateTimeString = \substr($dateTimeString, 0, 19) . '.';
            $micros = '';
            $length = 0;
        } else {
            $length = \strlen($micros);
        }

        if ($length < 6) {
            $micros .= \str_repeat('0', 6 - $length);
        } elseif ($length > 6) {
            $micros = \substr($micros, 0, 6);
        }

        $micros = \str_replace('+', '0', $micros);

        return \substr($dateTimeString, 0, 20) . $micros . 'Z';
    }
}
