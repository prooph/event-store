<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ProophTest\EventStore\Container\Aggregate;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Aggregate\AggregateTranslator;
use Prooph\EventStore\Aggregate\Exception\InvalidArgumentException;
use Prooph\EventStore\Container\Aggregate\AggregateRepositoryFactory;
use Prooph\EventStore\EventStore;
use ProophTest\EventStore\Mock\RepositoryMock;
use ProophTest\EventStore\Mock\User;
use ProophTest\EventStore\TestCase;

/**
 * Main tests can be found in AbstractAggregateRepositoryFactoryTest
 */
class AggregateRepositoryFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_aggregate_from_static_call()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'event_store' => [
                    'repository_mock' => [
                        'repository_class' => RepositoryMock::class,
                        'aggregate_type' => User::class,
                        'aggregate_translator' => 'user_translator',
                    ]
                ]
            ]
        ]);
        $container->get(EventStore::class)->willReturn($this->eventStore);

        $userTranslator = $this->prophesize(AggregateTranslator::class);

        $container->get('user_translator')->willReturn($userTranslator->reveal());

        $factory = [AggregateRepositoryFactory::class, 'repository_mock'];
        self::assertInstanceOf(RepositoryMock::class, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_without_container_on_static_call()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'The first argument must be of type Interop\Container\ContainerInterface'
        );
        AggregateRepositoryFactory::other_config_id();
    }
}
