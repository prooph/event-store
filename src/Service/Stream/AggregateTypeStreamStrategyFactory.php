<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.08.15 - 16:09
 */

namespace Prooph\EventStore\Service\Stream;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Stream\AggregateTypeStreamStrategy;

/**
 * Class AggregateTypeStreamStrategyFactory
 * @package Prooph\EventStore\Service\Stream
 */
final class AggregateTypeStreamStrategyFactory
{
    /**
     * @param ContainerInterface $container
     * @return AggregateTypeStreamStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        $aggregateTypeStreamMap = [];

        if ($container->has('config')) {
            $config = $container->get('config');

            if (isset($config['prooph']['event_store']['aggregate_type_stream_map'])) {
                $aggregateTypeStreamMap = $config['prooph']['event_store']['aggregate_type_stream_map'];
            }
        }

        return new AggregateTypeStreamStrategy($container->get('prooph.event_store'), $aggregateTypeStreamMap);
    }
}
