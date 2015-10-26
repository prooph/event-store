<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/26/15 - 9:05 PM
 */
namespace Prooph\EventStore\Container\Snapshot;

use Interop\Config\ConfigurationTrait;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Snapshot\SnapshotStore;

/**
 * Class SnapshotStoreFactory
 *
 * @package Prooph\EventStore\Container\Snapshot
 */
final class SnapshotStoreFactory implements RequiresConfig, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @param ContainerInterface $container
     * @return SnapshotStore
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $config = $this->options($config);

        $adapter = $container->get($config['adapter']['type']);

        return new SnapshotStore($adapter);
    }

    /**
     * @inheritdoc
     */
    public function vendorName()
    {
        return 'prooph';
    }

    /**
     * @inheritdoc
     */
    public function packageName()
    {
        return 'snapshot_store';
    }

    /**
     * @inheritdoc
     */
    public function mandatoryOptions()
    {
        return [
            'adapter' => [
                'type'
            ],
        ];
    }
}
