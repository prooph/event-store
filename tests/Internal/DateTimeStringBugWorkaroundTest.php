<?php


namespace ProophTest\EventStore\Internal;


use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Internal\DateTimeStringBugWorkaround;

class DateTimeStringBugWorkaroundTest extends TestCase
{
    public function test_it_correctly_converts_datetime_with_missing_micros()
    {
        $date = "2020-12-14T12:55:57Z";
        $expectedConvertedDate = "2020-12-14T12:55:57.000000Z";
        $convertedDate = DateTimeStringBugWorkaround::fixDateTimeString($date);
        $this->assertEquals($convertedDate, $expectedConvertedDate);
    }

    public function test_it_correctly_converts_datetime_with_more_than_6_micros()
    {
        $date = "2020-12-14T12:55:57.1234567Z";
        $expectedConvertedDate = "2020-12-14T12:55:57.123456Z";
        $convertedDate = DateTimeStringBugWorkaround::fixDateTimeString($date);
        $this->assertEquals($convertedDate, $expectedConvertedDate);
    }

    public function test_it_correctly_converts_datetime_with_less_than_6_micros()
    {
        $date = "2020-12-14T12:55:57.1234Z";
        $expectedConvertedDate = "2020-12-14T12:55:57.123400Z";
        $convertedDate = DateTimeStringBugWorkaround::fixDateTimeString($date);
        $this->assertEquals($convertedDate, $expectedConvertedDate);
    }
}