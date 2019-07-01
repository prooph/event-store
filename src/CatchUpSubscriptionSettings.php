<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Internal\Consts;

class CatchUpSubscriptionSettings
{
    /**
     * The maximum amount of events to cache when processing from a live subscription.
     * Going above this value will drop the subscription.
     *
     * @var int
     */
    private $maxLiveQueueSize;

    /**
     * The number of events to read per batch when reading the history.
     *
     * @var int
     */
    private $readBatchSize;

    /** @var bool */
    private $verboseLogging;

    /** @var bool */
    private $resolveLinkTos;

    /** @var string */
    private $subscriptionName;

    public function __construct(
        int $maxLiveQueueSize,
        int $readBatchSize,
        bool $verboseLogging,
        bool $resolveLinkTos,
        string $subscriptionName = ''
    ) {
        if ($readBatchSize < 1) {
            throw new InvalidArgumentException('Read batch size must be positive');
        }

        if ($maxLiveQueueSize < 1) {
            throw new InvalidArgumentException('Max live queue size must be positive');
        }

        if ($readBatchSize > Consts::MAX_READ_SIZE) {
            throw new InvalidArgumentException(\sprintf(
                'Read batch size should be less than \'%s\'. For larger reads you should page',
                Consts::MAX_READ_SIZE
            ));
        }

        $this->maxLiveQueueSize = $maxLiveQueueSize;
        $this->readBatchSize = $readBatchSize;
        $this->verboseLogging = $verboseLogging;
        $this->resolveLinkTos = $resolveLinkTos;
        $this->subscriptionName = $subscriptionName;
    }

    public static function default(): self
    {
        return new self(
            Consts::CATCH_UP_DEFAULT_MAX_PUSH_QUEUE_SIZE,
            Consts::CATCH_UP_DEFAULT_READ_BATCH_SIZE,
            false,
            true,
            ''
        );
    }

    public function maxLiveQueueSize(): int
    {
        return $this->maxLiveQueueSize;
    }

    public function readBatchSize(): int
    {
        return $this->readBatchSize;
    }

    public function verboseLogging(): bool
    {
        return $this->verboseLogging;
    }

    public function enableVerboseLogging(): self
    {
        $self = clone $this;
        $self->verboseLogging = true;

        return $self;
    }

    public function resolveLinkTos(): bool
    {
        return $this->resolveLinkTos;
    }

    public function subscriptionName(): string
    {
        return $this->subscriptionName;
    }
}
