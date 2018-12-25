<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\OutOfRangeException;

/**
 * All times are milliseconds
 */
class ClusterSettings
{
    /** @var string */
    private $clusterDns = '';
    /** @var int */
    private $maxDiscoverAttempts;
    /** @var int */
    private $externalGossipPort = 0;
    /** @var GossipSeed[] */
    private $gossipSeeds = [];
    /** @var int */
    private $gossipTimeout = 0;
    /** @var bool */
    private $preferRandomNode;

    public static function create(): ClusterSettingsBuilder
    {
        return new ClusterSettingsBuilder();
    }

    public static function fromGossipSeeds(
        array $gossipSeeds,
        int $maxDiscoverAttempts,
        int $gossipTimeout,
        bool $preferRandomNode
    ): self {
        $clusterSettings = new self();

        foreach ($gossipSeeds as $gossipSeed) {
            if (! $gossipSeed instanceof GossipSeed) {
                throw new InvalidArgumentException(\sprintf(
                    'Expected an array of %s',
                    GossipSeed::class
                ));
            }

            $clusterSettings->gossipSeeds[] = $gossipSeed;
        }

        $clusterSettings->maxDiscoverAttempts = $maxDiscoverAttempts;
        $clusterSettings->gossipTimeout = $gossipTimeout;
        $clusterSettings->preferRandomNode = $preferRandomNode;

        return $clusterSettings;
    }

    public static function fromClusterDns(
        string $clusterDns,
        int $maxDiscoverAttempts,
        int $externalGossipPort,
        int $gossipTimeout,
        bool $preferRandomNode
    ): self {
        $clusterSettings = new self();

        if (empty($clusterDns)) {
            throw new InvalidArgumentException(
                'Cluster DNS cannot be empty'
            );
        }

        if ($maxDiscoverAttempts < 1) {
            throw new OutOfRangeException(\sprintf(
                'Max discover attempts value is out of range: %d. Allowed range: [1, PHP_INT_MAX].',
                $maxDiscoverAttempts
            ));
        }

        if ($externalGossipPort < 1) {
            throw new OutOfRangeException(\sprintf(
                'External gossip port value is out of range: %d. Allowed range: [1, PHP_INT_MAX].',
                $externalGossipPort
            ));
        }

        $clusterSettings->clusterDns = $clusterDns;
        $clusterSettings->maxDiscoverAttempts = $maxDiscoverAttempts;
        $clusterSettings->externalGossipPort = $externalGossipPort;
        $clusterSettings->gossipTimeout = $gossipTimeout;
        $clusterSettings->preferRandomNode = $preferRandomNode;

        return $clusterSettings;
    }

    public function clusterDns(): string
    {
        return $this->clusterDns;
    }

    public function maxDiscoverAttempts(): int
    {
        return $this->maxDiscoverAttempts;
    }

    public function externalGossipPort(): int
    {
        return $this->externalGossipPort;
    }

    /** @return GossipSeed[] */
    public function gossipSeeds(): array
    {
        return $this->gossipSeeds;
    }

    public function gossipTimeout(): int
    {
        return $this->gossipTimeout;
    }

    public function preferRandomNode(): bool
    {
        return $this->preferRandomNode;
    }
}
