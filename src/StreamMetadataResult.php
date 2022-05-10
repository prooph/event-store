<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

class StreamMetadataResult
{
    private string $stream;

    private bool $isStreamDeleted;

    private int $metastreamVersion;

    private StreamMetadata $streamMetadata;

    /** @internal */
    public function __construct(
        string $stream,
        bool $isStreamDeleted,
        int $metastreamVersion,
        StreamMetadata $streamMetadata
    ) {
        if (empty($stream)) {
            throw new InvalidArgumentException('Stream cannot be empty');
        }

        $this->stream = $stream;
        $this->isStreamDeleted = $isStreamDeleted;
        $this->metastreamVersion = $metastreamVersion;
        $this->streamMetadata = $streamMetadata;
    }

    public function stream(): string
    {
        return $this->stream;
    }

    public function isStreamDeleted(): bool
    {
        return $this->isStreamDeleted;
    }

    public function metastreamVersion(): int
    {
        return $this->metastreamVersion;
    }

    public function streamMetadata(): StreamMetadata
    {
        return $this->streamMetadata;
    }
}
