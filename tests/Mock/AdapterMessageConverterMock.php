<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/14/15 - 9:26 PM
 */
namespace Prooph\EventStoreTest\Mock;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Adapter\AdapterMessageConverter;

final class AdapterMessageConverterMock
{
    use AdapterMessageConverter;

    private $options;

    public function __construct(array $options)
    {
        $this->initMessageConversion($options);
        $this->options = $options;
    }

    public function proxyToCreateMessageFromArray($messageName, $messageData)
    {
        return $this->createMessageFromArray($messageName, $messageData);
    }

    public function proxyToConvertMessageToArray(Message $message)
    {
        return $this->convertMessageToArray($message);
    }

    public function proxyToSerializePayload(array $payload)
    {
        return $this->serializePayload($payload);
    }

    public function proxyToUnserializePayload($payloadStr)
    {
        return $this->unserializePayload($payloadStr);
    }
}
