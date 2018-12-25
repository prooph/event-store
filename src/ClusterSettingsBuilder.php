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

class ClusterSettingsBuilder
{
    /**
     * Sets the client to discover nodes using a DNS name and a well-known port.
     */
    public function discoverClusterViaDns(): DnsClusterSettingsBuilder
    {
        return new DnsClusterSettingsBuilder();
    }

    /**
     * Sets the client to discover cluster nodes by specifying the IP endpoints of
     * one or more of the nodes.
     */
    public function discoverClusterViaGossipSeeds(): GossipSeedClusterSettingsBuilder
    {
        return new GossipSeedClusterSettingsBuilder();
    }
}
