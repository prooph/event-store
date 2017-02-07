<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Upcasting;

use Iterator;
use Prooph\Common\Messaging\Message;

final class UpcastingIterator implements Iterator
{
    /**
     * @var Upcaster
     */
    private $upcaster;

    /**
     * @var Iterator
     */
    private $innerIterator;

    /**
     * @var array
     */
    private $cache = [];

    public function __construct(Upcaster $upcaster, Iterator $iterator)
    {
        $this->upcaster = $upcaster;
        $this->innerIterator = $iterator;
    }

    public function current(): ?Message
    {
        if (! empty($this->cache)) {
            $message = array_shift($this->cache);

            return $message;
        }

        $message = $this->innerIterator->current();

        if (! $message instanceof Message) {
            return $message;
        }

        $messages = $this->upcaster->upcast($message);

        if (empty($messages)) {
            $this->next();

            if ($this->valid()) {
                return $this->current();
            }

            return null;
        }

        $message = array_shift($messages);

        $this->cache = $message;

        return $message;
    }

    public function next(): void
    {
        $this->innerIterator->next();
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
        return $this->innerIterator->valid();
    }

    public function rewind(): void
    {
        $this->innerIterator->rewind();
    }
}
