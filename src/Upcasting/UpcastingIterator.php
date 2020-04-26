<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Upcasting;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\StreamIterator\StreamIterator;

final class UpcastingIterator implements StreamIterator
{
    /**
     * @var Upcaster
     */
    private $upcaster;

    /**
     * @var StreamIterator
     */
    private $innerIterator;

    /**
     * @var array
     */
    private $storedMessages = [];

    public function __construct(Upcaster $upcaster, StreamIterator $iterator)
    {
        $this->upcaster = $upcaster;
        $this->innerIterator = $iterator;
    }

    public function current(): ?Message
    {
        if (! empty($this->storedMessages)) {
            return \reset($this->storedMessages);
        }

        $current = null;

        if (! $this->innerIterator instanceof \EmptyIterator) {
            $current = $this->innerIterator->current();
        }

        if (null === $current) {
            return null;
        }

        $this->storedMessages = $this->upcaster->upcast($current);

        while (empty($this->storedMessages)) {
            $this->innerIterator->next();

            if (! $this->innerIterator->valid()) {
                return null;
            }

            $this->storedMessages = $this->upcaster->upcast($this->innerIterator->current());
        }

        return \reset($this->storedMessages);
    }

    public function next(): void
    {
        if (! empty($this->storedMessages)) {
            \array_shift($this->storedMessages);
        }

        if (! empty($this->storedMessages)) {
            return;
        }

        while (empty($this->storedMessages)) {
            $this->innerIterator->next();

            if (! $this->innerIterator->valid()) {
                return;
            }

            $this->storedMessages = $this->upcaster->upcast($this->innerIterator->current());
        }
    }

    /**
     * @return bool|int
     */
    public function key()
    {
        return $this->innerIterator->key();
    }

    public function valid(): bool
    {
        if ($this->innerIterator instanceof \EmptyIterator) {
            return false;
        }

        return null !== $this->current();
    }

    public function rewind(): void
    {
        $this->storedMessages = [];
        $this->innerIterator->rewind();
        $this->position = 0;
    }

    public function count(): int
    {
        return $this->innerIterator->count();
    }
}
