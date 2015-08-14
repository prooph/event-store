<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 8/14/15 - 8:06 PM
 */

namespace Prooph\EventStore\Adapter;

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\Adapter\Exception\ConfigurationException;
use Prooph\EventStore\Adapter\Exception\RuntimeException;

/**
 * Trait AdapterMessageConverter
 *
 * This trait adds message conversion capabilities to any event store adapter.
 *
 * @package Prooph\EventStore\Adapter
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
 */
trait AdapterMessageConverter
{
    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var MessageConverter
     */
    protected $messageConverter;

    /**
     * @var PayloadSerializer
     */
    protected $payloadSerializer;

    /**
     * Pass adapter options to this method to initialize the trait
     *
     * @param array $adapterOptions
     * @throws Exception\ConfigurationException
     */
    protected function initMessageConversion(array $adapterOptions)
    {
        if (isset($adapterOptions['message_factory'])) {
            if (! $adapterOptions['message_factory'] instanceof MessageFactory) {
                throw new ConfigurationException(sprintf(
                    'Expected message_factory to be an instance of %s. Got %s',
                    MessageFactory::class,
                    is_object($adapterOptions['message_factory'])
                        ? get_class($adapterOptions['message_factory']) : gettype($adapterOptions['message_factory'])
                ));
            }

            $this->messageFactory = $adapterOptions['message_factory'];
        }

        if (isset($adapterOptions['message_converter'])) {
            if (! $adapterOptions['message_converter'] instanceof MessageConverter) {
                throw new ConfigurationException(sprintf(
                    'Expected message_converter to be an instance of %s. Got %s',
                    MessageConverter::class,
                    is_object($adapterOptions['message_converter'])
                        ? get_class($adapterOptions['message_converter']) : gettype($adapterOptions['message_converter'])
                ));
            }

            $this->messageConverter = $adapterOptions['message_converter'];
        }

        if (isset($adapterOptions['payload_serializer'])) {
            if (! $adapterOptions['payload_serializer'] instanceof PayloadSerializer) {
                throw new ConfigurationException(sprintf(
                    'Expected payload_serializer to be an instance of %s. Got %s',
                    PayloadSerializer::class,
                    is_object($adapterOptions['payload_serializer'])
                        ? get_class($adapterOptions['payload_serializer']) : gettype($adapterOptions['payload_serializer'])
                ));
            }

            $this->payloadSerializer = $adapterOptions['payload_serializer'];
        }
    }

    /**
     * @param string $messageName
     * @param array $messageArray
     * @return Message
     * @throws Exception\RuntimeException
     */
    protected function createMessageFromArray($messageName, array $messageArray)
    {
        if (null === $this->messageFactory) {
            throw $this->dependencyIsMissing('message factory', __METHOD__);
        }

        return $this->messageFactory->createMessageFromArray($messageName, $messageArray);
    }

    /**
     * @param Message $message
     * @return array
     * @throws Exception\RuntimeException if message converter is not set
     */
    protected function convertMessageToArray(Message $message)
    {
        if (null === $this->messageConverter) {
            throw $this->dependencyIsMissing('message converter', __METHOD__);
        }

        return $this->messageConverter->convertToArray($message);
    }

    /**
     * @param array $payload
     * @return string
     * @throws Exception\RuntimeException
     */
    protected function serializePayload(array $payload)
    {
        if (null === $this->payloadSerializer) {
            throw $this->dependencyIsMissing('payload serializer', __METHOD__);
        }

        return $this->payloadSerializer->serializePayload($payload);
    }

    /**
     * @param string $payloadStr
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function unserializePayload($payloadStr)
    {
        if (null === $this->payloadSerializer) {
            throw $this->dependencyIsMissing('payload serializer', __METHOD__);
        }

        return $this->payloadSerializer->unserializePayload($payloadStr);
    }

    /**
     * @param string $dependency
     * @param string $inMethod
     * @return RuntimeException
     */
    protected function dependencyIsMissing($dependency, $inMethod)
    {
        return new RuntimeException(sprintf(
            '%s::%s: not possible. No %s set. May forgot to invoke %s::%s?',
            get_class($this),
            $inMethod,
            $dependency,
            AdapterMessageConverter::class,
            'initMessageConversion'
        ));
    }
}
 