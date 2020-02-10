<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

class ConditionalWriteResult
{
    private ConditionalWriteStatus $status;
    private ?int $nextExpectedVersion;
    private ?Position $logPosition;

    private function __construct()
    {
    }

    public static function success(int $nextExpectedVersion, Position $logPosition): ConditionalWriteResult
    {
        $self = new self();

        $self->status = ConditionalWriteStatus::succeeded();
        $self->nextExpectedVersion = $nextExpectedVersion;
        $self->logPosition = $logPosition;

        return $self;
    }

    public static function fail(ConditionalWriteStatus $status): ConditionalWriteResult
    {
        if ($status->equals(ConditionalWriteStatus::succeeded())) {
            throw new InvalidArgumentException('For successful write pass next expected version and log position');
        }

        $self = new self();
        $self->status = $status;

        return $self;
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
