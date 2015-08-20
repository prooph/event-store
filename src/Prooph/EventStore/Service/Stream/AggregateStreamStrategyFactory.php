<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.08.15 - 16:06
 */

namespace Prooph\EventStore\Service\Stream;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Stream\AggregateStreamStrategy;

/**
 * Class AggregateStreamStrategyFactory
 * @package Prooph\EventStore\Service\Stream
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

        if ($container->has('configuration')) {
            $config = $container->get('configuration');

            if (isset($config['proophessor']['event_store']['aggregate_type_stream_map'])) {
                $aggregateTypeStreamMap = $config['proophessor']['event_store']['aggregate_type_stream_map'];
            }
        }

        return new AggregateStreamStrategy($container->get('proophessor.event_store'), $aggregateTypeStreamMap);
    }
}
