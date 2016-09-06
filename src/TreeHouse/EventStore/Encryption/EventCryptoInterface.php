<?php

namespace TreeHouse\EventStore\Encryption;

interface EventCryptoInterface
{
    /**
     * @param string $eventName
     *
     * @return bool
     */
    public function supports($eventName);

    /**
     * @param mixed  $payload
     * @param int    $payloadVersion
     * @param string $encryptionKey
     *
     * @return mixed
     */
    public function encrypt($payload, $payloadVersion, $encryptionKey);

    /**
     * @param mixed  $payload
     * @param int    $payloadVersion
     * @param string $encryptionKey
     *
     * @return mixed
     */
    public function decrypt($payload, $payloadVersion, $encryptionKey);
}
