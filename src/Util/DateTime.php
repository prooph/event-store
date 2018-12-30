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

use DateTimeImmutable;
use DateTimeZone;

class DateTime
{
    public static function utcNow(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function create(string $dateTimeString): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.uP',
            $dateTimeString,
            new DateTimeZone('UTC')
        );
    }

    public static function format(DateTimeImmutable $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:s.uP');
    }

    final private function __construct()
    {
    }
}
