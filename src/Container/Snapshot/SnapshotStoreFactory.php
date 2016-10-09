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

namespace Prooph\EventStore\Container\Snapshot;

use Interop\Config\ConfigurationTrait;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Snapshot\SnapshotStore;

/**
 * Class SnapshotStoreFactory
 *
 * @package Prooph\EventStore\Container\Snapshot
 */
final class SnapshotStoreFactory implements RequiresConfig, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $configId;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'prooph.event_store.service_name' => [EventStoreFactory::class, 'service_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): SnapshotStore
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $configId = 'default')
    {
        $this->configId = $configId;
    }

    public function __invoke(ContainerInterface $container): SnapshotStore
    {
        $config = $container->get('config');
        $config = $this->options($config, $this->configId);

        $adapter = $container->get($config['adapter']['type']);

        return new SnapshotStore($adapter);
    }

    /**
     * {@inheritdoc}
     */
    public function dimensions(): array
    {
        return ['prooph', 'snapshot_store'];
    }

    /**
     * {@inheritdoc}
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
