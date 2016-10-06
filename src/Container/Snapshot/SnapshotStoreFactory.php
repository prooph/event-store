<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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

    public function __invoke(ContainerInterface $container): SnapshotStore
    {
        $config = $container->get('config');
        $config = $this->options($config);

        $adapter = $container->get($config['adapter']['type']);

        return new SnapshotStore($adapter);
    }

    /**
     * @inheritdoc
     */
    public function dimensions(): array
    {
        return ['prooph', 'snapshot_store'];
    }

    /**
     * @inheritdoc
     */
    public function mandatoryOptions(): array
    {
        return [
            'adapter' => [
                'type'
            ],
        ];
    }
}
