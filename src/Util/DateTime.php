<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Util;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class DateTime
{
    public static function utcNow(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function create(string $dateTimeString): DateTimeImmutable
    {
        $dateTime = DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.uP',
            $dateTimeString,
            new DateTimeZone('UTC')
        );

        if ($dateTime === false) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Could not create DateTimeImmutable from string "%s".',
                    $dateTimeString
                )
            );
        }

        return $dateTime;
    }

    public static function format(DateTimeImmutable $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:s.uP');
    }

    final private function __construct()
    {
    }
}
