<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Internal;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Internal\DateTimeStringBugWorkaround;

class DateTimeStringBugWorkaroundTest extends TestCase
{
    public function provider(): array
    {
        return [
            [
                '2020-12-14T12:55:57Z',
                '2020-12-14T12:55:57.000000Z',
            ],
            [
                '2020-12-14T12:55:57.1234567Z',
                '2020-12-14T12:55:57.123456Z',
            ],
            [
                '2020-12-14T12:55:57.1234Z',
                '2020-12-14T12:55:57.123400Z',
            ],
        ];
    }

    /**
     * @param string $date
     * @param string $expectedConvertedDate
     * @dataProvider provider
     */
    public function test_it_correctly_converts_datetime(string $date, string $expectedConvertedDate): void
    {
        $actualConvertedDate = DateTimeStringBugWorkaround::fixDateTimeString($date);
        $this->assertEquals($expectedConvertedDate, $actualConvertedDate);
    }
}
