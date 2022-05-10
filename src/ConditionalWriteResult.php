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

/** @psam-immutable */
class ConditionalWriteResult
{
    private function __construct(
        private readonly ConditionalWriteStatus $status,
        private readonly ?int $nextExpectedVersion,
        private readonly ?Position $logPosition
    ) {
    }

    public static function success(int $nextExpectedVersion, Position $logPosition): ConditionalWriteResult
    {
        return new self(ConditionalWriteStatus::Succeeded, $nextExpectedVersion, $logPosition);
    }

    public static function fail(ConditionalWriteStatus $status): ConditionalWriteResult
    {
        if ($status === ConditionalWriteStatus::Succeeded) {
            throw new InvalidArgumentException('For successful write pass next expected version and log position');
        }

        return new self($status, null, null);
    }

    public function status(): ConditionalWriteStatus
    {
        return $this->status;
    }

    public function nextExpectedVersion(): ?int
    {
        return $this->nextExpectedVersion;
    }

    public function logPosition(): ?Position
    {
        return $this->logPosition;
    }
}
