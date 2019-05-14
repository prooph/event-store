<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamIterator;

use Prooph\Common\Messaging\Message;

class MergedStreamIterator implements StreamIterator
{
    /**
     * @var StreamIterator[]
     */
    private $iterators;

    /**
     * @var string[]
     */
    private $streamNames;

    public function __construct(array $streamNames, StreamIterator ...$iterators)
    {
        $this->iterators = [];
        $this->streamNames = $streamNames;

        foreach ($iterators as $key => $iterator) {
            $this->iterators[$key] = $iterator;
        }

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        foreach ($this->iterators as $iter) {
            $iter->rewind();
        }

        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        foreach ($this->iterators as $key => $iterator) {
            if ($iterator->valid()) {
                return true;
            }
        }

        return false;
    }

    public function next(): void
    {
        // only advance the prioritized iterator
        $this->iterators[\array_keys($this->iterators)[0]]->next();

        $this->prioritizeIterators();
    }

    public function current()
    {
        return $this->iterators[\array_keys($this->iterators)[0]]->current();
    }

    public function streamName(): string
    {
        return $this->streamNames[\array_keys($this->iterators)[0]];
    }

    public function key(): int
    {
        return $this->iterators[\array_keys($this->iterators)[0]]->key();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->iterators as $iterator) {
            $count += \count($iterator);
        }

        return $count;
    }

    /**
     * Will prioritize iterators via sorting them according to;
     *
     * 1) invalid iterators are placed last.
     * 2) by comparing the createdAt of the current prooph message of the iterator.
     */
    private function prioritizeIterators(): void
    {
        $compareValue = function (\Iterator $iterator): \DateTimeImmutable {
            /** @var Message $message */
            $message = $iterator->current();

            return $message->createdAt();
        };

        $compareFunction = function (\Iterator $a, \Iterator $b) use ($compareValue) {
            // valid iterators should be prioritized over invalid ones
            if (! $a->valid() or ! $b->valid()) {
                return $b->valid() <=> $a->valid();
            }

            return $compareValue($a) <=> $compareValue($b);
        };

        \uasort($this->iterators, $compareFunction);
    }
}
