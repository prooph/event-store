<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 8/14/15 - 9:06 PM
 */
namespace Prooph\EventStoreTest\Adapter;

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\Adapter\AdapterMessageConverter;
use Prooph\EventStore\Adapter\PayloadSerializer;
use Prooph\EventStoreTest\Mock\AdapterMessageConverterMock;
use Prooph\EventStoreTest\TestCase;

final class AdapterMessageConverterTest extends TestCase
{
    /**
     * @test
     */
    function it_uses_message_factory_passed_via_adapter_options_to_create_message_from_array()
    {
        $messageFactory = $this->prophesize(MessageFactory::class);

        $messageFactory->createMessageFromArray("test_message", [])->shouldBeCalled();

        $adapter = new AdapterMessageConverterMock(['message_factory' => $messageFactory->reveal()]);

        $adapter->proxyToCreateMessageFromArray('test_message', []);
    }

    /**
     * @test
     */
    function it_uses_message_converter_passed_via_adapter_options_to_convert_message_to_array()
    {
        $message = $this->prophesize(Message::class);

        $messageConverter = $this->prophesize(MessageConverter::class);

        $messageConverter->convertToArray($message->reveal())->shouldBeCalled();

        $adapter = new AdapterMessageConverterMock(['message_converter' => $messageConverter->reveal()]);

        $adapter->proxyToConvertMessageToArray($message->reveal());

    }

    /**
     * @test
     */
    function it_uses_payload_serializer_passed_via_adapter_options_to_serialize_payload_array()
    {
        $serializer = $this->prophesize(PayloadSerializer::class);

        $serializer->serializePayload([])->shouldBeCalled();

        $adapter = new AdapterMessageConverterMock(['payload_serializer' => $serializer->reveal()]);

        $adapter->proxyToSerializePayload([]);

    }

    /**
     * @test
     */
    function it_uses_payload_serializer_passed_via_adapter_options_to_unserialize_payload_string()
    {
        $serializer = $this->prophesize(PayloadSerializer::class);

        $serializer->unserializePayload("payload")->shouldBeCalled();

        $adapter = new AdapterMessageConverterMock(['payload_serializer' => $serializer->reveal()]);

        $adapter->proxyToUnserializePayload("payload");
    }

    /**
     * @test
     * @dataProvider provideWrongDependency
     * @expectedException \Prooph\EventStore\Adapter\Exception\ConfigurationException
     */
    function it_throws_configuration_exception_if_passed_dependency_has_the_wrong_type(array $adapterOptions)
    {
        new AdapterMessageConverterMock($adapterOptions);
    }

    function provideWrongDependency()
    {
        return [
            [['message_factory' => 'wrong type']],
            [['message_converter' => 'wrong type']],
            [['payload_serializer' => 'wrong type']],
        ];
    }

    /**
     * @test
     * @dataProvider provideMethodCalls
     * @expectedException \Prooph\EventStore\Adapter\Exception\RuntimeException
     */
    function it_throws_exception_at_runtime_if_required_dependency_was_not_set($method, $methodArgs)
    {
        $adapter = new AdapterMessageConverterMock([]);

        call_user_func_array([$adapter, 'proxyTo'.ucfirst($method)], $methodArgs);
    }

    function provideMethodCalls()
    {
        $message = $this->prophesize(Message::class);

        return [
            ["createMessageFromArray", ["test_message", []]],
            ["convertMessageToArray", [$message->reveal()]],
            ["serializePayload", [[]]],
            ["unserializePayload", ["payload"]],
        ];
    }
}
