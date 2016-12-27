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

namespace Prooph\EventStore\Projection;

use Prooph\EventStore\Exception\RuntimeException;

trait InMemoryEventStoreQueryTrait
{
    /**
     * @var array
     */
    private $knownStreams;

    protected function buildKnownStreams()
    {
        $reflectionProperty = new \ReflectionProperty(get_class($this->eventStore), 'streams');
        $reflectionProperty->setAccessible(true);

        $this->knownStreams = array_keys($reflectionProperty->getValue($this->eventStore));
    }

    public function fromCategory(string $name): Query
    {
        if (null !== $this->streamPositions) {
            throw new RuntimeException('from was already called');
        }

        $this->streamPositions = [];

        foreach ($this->knownStreams as $stream) {
            if (substr($stream, 0, strlen($name) + 1) === $name . '-') {
                $this->streamPositions[$stream] = 0;
            }
        }

        return $this;
    }

    public function fromCategories(string ...$names): Query
    {
        if (null !== $this->streamPositions) {
            throw new RuntimeException('from was already called');
        }

        $this->streamPositions = [];

        foreach ($this->knownStreams as $stream) {
            foreach ($names as $name) {
                if (substr($stream, 0, strlen($name) + 1) === $name . '-') {
                    $this->streamPositions[$stream] = 0;
                    break;
                }
            }
        }

        return $this;
    }

    public function fromAll(): Query
    {
        if (null !== $this->streamPositions) {
            throw new RuntimeException('from was already called');
        }

        $this->streamPositions = [];

        foreach ($this->knownStreams as $stream) {
            if (substr($stream, 0, 1) === '$') {
                // ignore internal streams
                continue;
            }
            $this->streamPositions[$stream] = 0;
        }

        return $this;
    }
}
