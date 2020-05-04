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

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

/** @psalm-immutable */
class PersistentSubscriptionNakEventAction
{
    public const OPTIONS = [
        'Unknown' => 0,
        'Park' => 1,
        'Retry' => 2,
        'Skip' => 3,
        'Stop' => 4,
    ];

    // Client unknown on action. Let server decide
    public const UNKNOWN = 0;
    // Park message do not resend. Put on poison queue
    public const PARK = 1;
    // Explicitly retry the message
    public const RETRY = 2;
    // Skip this message do not resend do not put in poison queue
    public const SKIP = 3;
    // Stop the subscription
    public const STOP = 4;

    private string $name;
    private int $value;

    private function __construct(string $name)
    {
        $this->name = $name;
        $this->value = self::OPTIONS[$name];
    }

    public static function unknown(): self
    {
        return new self('Unknown');
    }

    public static function park(): self
    {
        return new self('Park');
    }

    public static function retry(): self
    {
        return new self('Retry');
    }

    public static function skip(): self
    {
        return new self('Skip');
    }

    public static function stop(): self
    {
        return new self('Stop');
    }

    public static function byName(string $value): self
    {
        if (! isset(self::OPTIONS[$value])) {
            throw new InvalidArgumentException('Unknown enum name given');
        }

        return new self($value);
    }

    public static function byValue(int $value): self
    {
        foreach (self::OPTIONS as $name => $v) {
            if ($v === $value) {
                return new self($name);
            }
        }

        throw new InvalidArgumentException('Unknown enum value given');
    }

    /** @psalm-pure */
    public function equals(PersistentSubscriptionNakEventAction $other): bool
    {
        return \get_class($this) === \get_class($other) && $this->name === $other->name;
    }

    /** @psalm-pure */
    public function name(): string
    {
        return $this->name;
    }

    /** @psalm-pure */
    public function value(): int
    {
        return $this->value;
    }

    /** @psalm-pure */
    public function __toString(): string
    {
        return $this->name;
    }
}
