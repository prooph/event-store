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

use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

class Guid
{
    /** @var UuidFactory */
    private static $factory;

    public static function generate(): UuidInterface
    {
        return self::factory()->uuid4();
    }

    public static function generateString(): string
    {
        return self::generate()->toString();
    }

    public static function generateAsHex(): string
    {
        return self::generate()->getHex();
    }

    public static function fromString(string $uuid): UuidInterface
    {
        return self::factory()->fromString($uuid);
    }

    public static function fromBytes(string $bytes): UuidInterface
    {
        return self::factory()->fromBytes($bytes);
    }

    public static function empty(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }

    final private function __construct()
    {
    }

    private static function factory(): UuidFactory
    {
        if (null === self::$factory) {
            self::$factory = new UuidFactory(new FeatureSet(true));
        }

        return self::$factory;
    }
}
