<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Adapter\PayloadSerializer;

use Prooph\EventStore\Adapter\PayloadSerializer\JsonPayloadSerializer;
use ProophTest\EventStore\TestCase;

/**
 * Class JsonPayloadSerializerTest
 * @package ProophTest\EventStore\Adapter\PayloadSerializer
 */
final class JsonPayloadSerializerTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePayload
     */
    public function it_serializes_and_unserializes_a_payload_array(array $payload)
    {
        $serializer = new JsonPayloadSerializer();

        $payloadStr = $serializer->serializePayload($payload);

        $payloadCopy = $serializer->unserializePayload($payloadStr);

        $this->assertEquals($payload, $payloadCopy);
    }

    public function providePayload()
    {
        return [
            [
                ['string' => 'payload'],
                ['bool_true' => true, 'bool_false' => false],
                ['int' => 1234],
                ['float' => 10.2],
                ['null' => null],
                ['array' => ['nested' => ['data' => 'structure']]],
            ]
        ];
    }
}
