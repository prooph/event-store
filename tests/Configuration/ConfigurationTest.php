<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStoreTest\Configuration;

use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\Feature;
use Prooph\EventStoreTest\Mock\AdapterMock;
use Prooph\EventStoreTest\Mock\EventLoggerFeature;
use Prooph\EventStoreTest\TestCase;

final class ConfigurationTest extends TestCase
{
    /**
     * @test
     */
    function it_defaults_to_fqcn_message_factory_if_no_alternative_is_set()
    {
        $config = new Configuration();

        $messageFactory = $config->getMessageFactory();

        $this->assertInstanceOf(FQCNMessageFactory::class, $messageFactory);
    }

    /**
     * @test
     */
    function it_defaults_to_no_op_message_converter_if_no_alternative_is_set()
    {
        $config = new Configuration();

        $converter = $config->getMessageConverter();

        $this->assertInstanceOf(NoOpMessageConverter::class, $converter);
    }

    /**
     * @test
     */
    function it_defaults_to_prooph_action_event_emitter_if_no_alternative_is_set()
    {
        $config = new Configuration();

        $emitter = $config->getActionEventEmitter();

        $this->assertInstanceOf(ProophActionEventEmitter::class, $emitter);
    }

    /**
     * @test
     */
    function it_uses_alternative_message_factory_if_set()
    {
        $factory = $this->prophesize(MessageFactory::class);

        $config = new Configuration();

        $config->setMessageFactory($factory->reveal());

        $this->assertSame($factory->reveal(), $config->getMessageFactory());
    }

    /**
     * @test
     */
    function it_uses_alternative_message_converter_if_set()
    {
        $converter = $this->prophesize(MessageConverter::class);

        $config = new Configuration();

        $config->setMessageConverter($converter->reveal());

        $this->assertSame($converter->reveal(), $config->getMessageConverter());
    }

    /**
     * @test
     */
    function it_uses_alternative_event_emitter_if_set()
    {
        $emitter = $this->prophesize(ActionEventEmitter::class);

        $config = new Configuration();

        $config->setActionEventEmitter($emitter->reveal());

        $this->assertSame($emitter->reveal(), $config->getActionEventEmitter());
    }

    /**
     * @test
     */
    function it_uses_message_factory_from_config_array_if_present()
    {
        $factory = $this->prophesize(MessageFactory::class);

        $config = new Configuration([
            'message_factory' => $factory->reveal(),
        ]);

        $this->assertSame($factory->reveal(), $config->getMessageFactory());
    }

    /**
     * @test
     */
    function it_uses_message_converter_from_config_array_if_present()
    {
        $converter = $this->prophesize(MessageConverter::class);

        $config = new Configuration([
            'message_converter' => $converter->reveal(),
        ]);

        $this->assertSame($converter->reveal(), $config->getMessageConverter());
    }

    /**
     * @test
     */
    function it_uses_event_emitter_from_config_array_if_present()
    {
        $emitter = $this->prophesize(ActionEventEmitter::class);

        $config = new Configuration([
            'action_event_emitter' => $emitter->reveal(),
        ]);

        $this->assertSame($emitter->reveal(), $config->getActionEventEmitter());
    }

    /**
     * @test
     */
    function it_adds_features_to_event_store_defined_via_configuration()
    {
        $eventStore = $this->prophesize(EventStore::class);

        $feature = $this->prophesize(Feature::class);

        $feature->setUp($eventStore->reveal())->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('test_feature')->willReturn($feature->reveal());

        $config = new Configuration([
            'feature_manager' => $container->reveal(),
            'features' => ['test_feature']
        ]);

        $config->setUpEventStoreEnvironment($eventStore->reveal());
    }

    /**
     * @test
     */
    function it_uses_feature_manager_added_via_setter_if_not_passed_via_config_array()
    {
        $eventStore = $this->prophesize(EventStore::class);

        $feature = $this->prophesize(Feature::class);

        $feature->setUp($eventStore->reveal())->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('test_feature')->willReturn($feature->reveal());

        $config = new Configuration([
            'features' => ['test_feature']
        ]);

        $config->setFeatureManager($container->reveal());

        $config->setUpEventStoreEnvironment($eventStore->reveal());
    }

    /**
     * @test
     */
    function it_does_not_apply_features_when_no_feature_manager_is_set()
    {
        $eventStore = $this->prophesize(EventStore::class);

        $feature = $this->prophesize(Feature::class);

        $feature->setUp($eventStore->reveal())->shouldNotBeCalled();

        $config = new Configuration([
            'features' => ['test_feature']
        ]);

        $config->setUpEventStoreEnvironment($eventStore->reveal());
    }

    /**
     * @test
     */
    function it_allows_setting_an_adapter()
    {
        $adapter = $this->prophesize(Adapter::class);

        $config = new Configuration();

        $config->setAdapter($adapter->reveal());

        $this->assertSame($adapter->reveal(), $config->getAdapter());
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Configuration\Exception\ConfigurationException
     */
    function it_throws_exception_if_adapter_was_not_set()
    {
        $config = new Configuration();

        $config->getAdapter();
    }

    /**
     * @test
     */
    function it_uses_adapter_from_config_array_if_specified()
    {
        $adapter = $this->prophesize(Adapter::class);

        $config = new Configuration([
            'adapter' => $adapter->reveal(),
        ]);

        $this->assertSame($adapter->reveal(), $config->getAdapter());
    }

    /**
     * @test
     */
    function it_set_up_a_new_adapter_from_adapter_config_using_type_and_options_enriched_with_message_factory_and_message_converter()
    {
        $messageFactory = $this->prophesize(MessageFactory::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $config = new Configuration([
            'adapter' => [
                'type' => AdapterMock::class,
                'options' => [
                    'connection' => 'db connection'
                ]
            ]
        ]);

        $config->setMessageFactory($messageFactory->reveal());
        $config->setMessageConverter($messageConverter->reveal());

        /** @var $adapter AdapterMock */
        $adapter = $config->getAdapter();

        $this->assertEquals([
            'connection' => 'db connection',
            'message_factory' => $messageFactory->reveal(),
            'message_converter' => $messageConverter->reveal()
        ], $adapter->getInjectedOptions());
    }

    /**
     * @test
     */
    function it_only_adds_message_factory_and_message_conveter_to_adapter_options_if_keys_are_not_set_already()
    {
        $messageFactory = $this->prophesize(MessageFactory::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $config = new Configuration([
            'adapter' => [
                'type' => AdapterMock::class,
                'options' => [
                    'connection' => 'db connection',
                    'message_factory' => 'custom_factory',
                    'message_converter' => 'custom_converter'
                ]
            ]
        ]);

        $config->setMessageFactory($messageFactory->reveal());
        $config->setMessageConverter($messageConverter->reveal());

        /** @var $adapter AdapterMock */
        $adapter = $config->getAdapter();

        $this->assertEquals([
            'connection' => 'db connection',
            'message_factory' => 'custom_factory',
            'message_converter' => 'custom_converter'
        ], $adapter->getInjectedOptions());
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Configuration\Exception\ConfigurationException
     */
    function it_throws_exception_if_adapter_type_is_missing()
    {
        $config = new Configuration([
            'adapter' => [
                'options' => [
                    'connection' => 'db connection',
                    'message_factory' => 'custom_factory',
                    'message_converter' => 'custom_converter'
                ]
            ]
        ]);

        $config->getAdapter();
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Configuration\Exception\ConfigurationException
     */
    function it_throws_exception_if_adapter_type_does_not_implement_adapter_interface()
    {
        $config = new Configuration([
            'adapter' => [
                'type' => EventLoggerFeature::class,
                'options' => [
                    'connection' => 'db connection',
                    'message_factory' => 'custom_factory',
                    'message_converter' => 'custom_converter'
                ]
            ]
        ]);

        $config->getAdapter();
    }

    /**
     * @test
     */
    function it_pass_options_with_message_factory_and_message_converter_to_adapter_even_if_no_options_are_specified_in_config()
    {
        $messageFactory = $this->prophesize(MessageFactory::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $config = new Configuration([
            'adapter' => [
                'type' => AdapterMock::class,
            ]
        ]);

        $config->setMessageFactory($messageFactory->reveal());
        $config->setMessageConverter($messageConverter->reveal());

        /** @var $adapter AdapterMock */
        $adapter = $config->getAdapter();

        $this->assertEquals([
            'message_factory' => $messageFactory->reveal(),
            'message_converter' => $messageConverter->reveal()
        ], $adapter->getInjectedOptions());
    }
}
