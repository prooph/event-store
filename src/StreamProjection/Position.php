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

namespace Prooph\EventStore\StreamProjection;

use Prooph\EventStore\Exception\InvalidArgumentException;

class Position
{
    private $streams = [];

    public function __construct(array $streamNames)
    {
        foreach ($streamNames as $streamName => $position) {
            $this->streams[$streamName] = $position;
        }
    }

    public function merge(array $streamNames)
    {
        $this->streams = array_merge($this->streams, $streamNames);
    }

    public function add(string ...$streamNames)
    {
        foreach ($streamNames as $streamName) {
            $this->streams[$streamName] = 0;
        }
    }

    public function inc(string $streamName)
    {
        if (! isset($this->streams[$streamName])) {
            throw new InvalidArgumentException('Invalid stream name given');
        }

        $this->streams[$streamName]++;
    }

    public function pos(string $streamName): int
    {
        if (! isset($this->streams[$streamName])) {
            throw new InvalidArgumentException('Invalid stream name given');
        }

        return $this->streams[$streamName];
    }

    public function reset(): void
    {
        $this->streams = array_map(
            function () {
                return 0;
            },
            $this->streams
        );
    }

    public function streamPositions(): array
    {
        return $this->streams;
    }
}
