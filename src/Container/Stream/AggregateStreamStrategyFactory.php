<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/20/15 - 06:12 PM
 */

namespace Prooph\EventStore\Container\Stream;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Stream\AggregateStreamStrategy;

/**
 * Class AggregateStreamStrategyFactory
 * @package Prooph\EventStore\Container\Stream
 */
final class AggregateStreamStrategyFactory
{
    /**
     * @param ContainerInterface $container
     * @return AggregateStreamStrategy
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

        return new AggregateStreamStrategy($container->get('prooph.event_store'), $aggregateTypeStreamMap);
    }
}
