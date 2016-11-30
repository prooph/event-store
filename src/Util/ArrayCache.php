<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Util;

class ArrayCache
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $position = -1;

    public function __construct(int $size)
    {
        if ($size < 0) {
            throw new \InvalidArgumentException('Size must be a positive integer');
        }

        $this->size = $size;
        $this->container = array_fill(0, $size, null);
    }

    /**
     * @param mixed $value
     */
    public function append($value): void
    {
        $this->container[$this->nextPosition()] = $value;
    }

    /**
     * @param int $position
     * @return mixed
     */
    public function get(int $position)
    {
        if ($position >= $this->size
            || $position < 0
        ) {
            throw new \InvalidArgumentException('Position must be between 0 and ' . ($this->size - 1));
        }

        return $this->container[$position];
    }

    public function has($value): bool
    {
        return in_array($value, $this->container, true);
    }

    public function size(): int
    {
        return $this->size;
    }

    private function nextPosition(): int
    {
        $this->position++;

        if ($this->position === $this->size()) {
            $this->position = 0;
        }

        return $this->position;
    }
}
