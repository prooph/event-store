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

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

/**
 * Transaction File Position
 *
 * @psalm-immutable
 */
class Position
{
    private int $commitPosition;
    private int $preparePosition;

    /** @internal */
    public function __construct(int $commitPosition, int $preparePosition)
    {
        $this->commitPosition = $commitPosition;
        $this->preparePosition = $preparePosition;
    }

    public static function invalid(): Position
    {
        return new Position(-1, -1);
    }

    public static function headOfTf(): Position
    {
        return new Position(-1, -1);
    }

    public static function start(): Position
    {
        return new Position(0, 0);
    }

    public static function end(): Position
    {
        return new Position(-1, -1);
    }

    public static function parse(string $string): Position
    {
        if (\strlen($string) !== 32) {
            throw new InvalidArgumentException('string too short');
        }

        $commitPosition = (int) \hexdec(\substr($string, 0, 16));
        $preparePosition = (int) \hexdec(\substr($string, 16, 16));

        return new Position($commitPosition, $preparePosition);
    }

    /** @psalm-mutation-free */
    public function commitPosition(): int
    {
        return $this->commitPosition;
    }

    /** @psalm-mutation-free */
    public function preparePosition(): int
    {
        return $this->preparePosition;
    }

    /** @psalm-mutation-free */
    public function asString(): string
    {
        return \substr('000000000000000' . \dechex($this->commitPosition), -16)
            . \substr('000000000000000' . \dechex($this->preparePosition), -16);
    }

    /** @psalm-mutation-free */
    public function __toString(): string
    {
        return 'C:' . $this->commitPosition . '/P:' . $this->preparePosition;
    }

    /** @psalm-mutation-free */
    public function equals(Position $other): bool
    {
        return $this->commitPosition === $other->commitPosition && $this->preparePosition === $other->preparePosition;
    }

    /** @psalm-mutation-free */
    public function greater(Position $other): bool
    {
        return $this->commitPosition > $other->commitPosition
            || ($this->commitPosition === $other->commitPosition && $this->preparePosition > $other->preparePosition);
    }

    /** @psalm-mutation-free */
    public function smaller(Position $other): bool
    {
        return $this->commitPosition < $other->commitPosition
            || ($this->commitPosition === $other->commitPosition && $this->preparePosition < $other->preparePosition);
    }

    /** @psalm-mutation-free */
    public function greaterOrEquals(Position $other): bool
    {
        return $this->greater($other) || $this->equals($other);
    }

    /** @psalm-mutation-free */
    public function smallerOrEquals(Position $other): bool
    {
        return $this->smaller($other) || $this->equals($other);
    }
}
