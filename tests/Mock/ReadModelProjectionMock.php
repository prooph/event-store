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

namespace ProophTest\EventStore\Mock;

use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Projection\ReadModelProjection;

class ReadModelProjectionMock implements ReadModelProjection
{
    private $storage;

    public function insert(string $key, $value): void
    {
        $this->storage[$key] = $value;
    }

    public function update(string $key, $value): void
    {
        if (! array_key_exists($key, $this->storage)) {
            throw new InvalidArgumentException('Invalid key given');
        }

        $this->storage[$key] = $value;
    }

    public function read(string $key)
    {
        return $this->storage[$key];
    }

    public function initProjection(): void
    {
        $this->storage = [];
    }

    public function projectionIsInitialized(): bool
    {
        return is_array($this->storage);
    }

    public function resetProjection(): void
    {
        $this->storage = [];
    }

    public function deleteProjection(): void
    {
        $this->storage = [];
    }
}
