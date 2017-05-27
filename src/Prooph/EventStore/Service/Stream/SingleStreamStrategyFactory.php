<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.08.15 - 16:36
 */

namespace Prooph\EventStore\Service\Stream;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Stream\SingleStreamStrategy;

/**
 * Class SingleStreamStrategyFactory
 * @package Prooph\EventStore\Service\Stream
 */
final class SingleStreamStrategyFactory
{
    /**
     * @param ContainerInterface $container
     * @return SingleStreamStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        $singleStreamName = null;

        if ($container->has('configuration')) {
            $config = $container->get('configuration');

            if (isset($config['proophessor']['event_store']['single_stream_name'])) {
                $singleStreamName = $config['proophessor']['event_store']['single_stream_name'];
            }
        }

        return new SingleStreamStrategy($container->get('proophessor.event_store'), $singleStreamName);
    }
}
