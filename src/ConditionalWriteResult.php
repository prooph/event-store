<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

/** @psam-immutable */
class ConditionalWriteResult
{
    private ConditionalWriteStatus $status;
    private ?int $nextExpectedVersion;
    private ?Position $logPosition;

    private function __construct(ConditionalWriteStatus $status, ?int $nextExpectedVersion, ?Position $logPosition)
    {
        $this->status = $status;
        $this->nextExpectedVersion = $nextExpectedVersion;
        $this->logPosition = $logPosition;
    }

    public static function success(int $nextExpectedVersion, Position $logPosition): ConditionalWriteResult
    {
        return new self(ConditionalWriteStatus::succeeded(), $nextExpectedVersion, $logPosition);
    }

    public static function fail(ConditionalWriteStatus $status): ConditionalWriteResult
    {
        if ($status->equals(ConditionalWriteStatus::succeeded())) {
            throw new InvalidArgumentException('For successful write pass next expected version and log position');
        }

        return new self($status, null, null);
    }

    /** @psalm-pure */
    public function status(): ConditionalWriteStatus
    {
        return $this->status;
    }

    /** @psalm-pure */
    public function nextExpectedVersion(): ?int
    {
        return $this->nextExpectedVersion;
    }

    /** @psalm-pure */
    public function logPosition(): ?Position
    {
        return $this->logPosition;
    }
}
