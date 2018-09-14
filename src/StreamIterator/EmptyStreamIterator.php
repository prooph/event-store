<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamIterator;

final class EmptyStreamIterator implements IterableStream
{
    public function current()
    {
        throw new \BadMethodCallException('Accessing the value of an EmptyIterator');
    }

    public function next(): void
    {
    }

    public function key()
    {
        throw new \BadMethodCallException('Accessing the key of an EmptyIterator');
    }

    public function valid(): bool
    {
        return false;
    }

    public function rewind(): void
    {
    }

    public function count(): int
    {
        return 0;
    }
}
