<?php

namespace TreeHouse\EventStore\Encryption;

use TreeHouse\EventStore\EventStreamInterface;

class CryptoEventStreamFactory
{
    /**
     * @var EventCryptoInterface[]
     */
    protected $cryptos = [];

    /**
     * @var string
     */
    protected $encryptionKey;

    /**
     * @param string                 $encryptionKey
     * @param EventCryptoInterface[] $cryptos
     */
    public function __construct($encryptionKey, array $cryptos = [])
    {
        $this->cryptos = $cryptos;
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * @param EventCryptoInterface $crypto
     */
    public function registerCrypto(EventCryptoInterface $crypto)
    {
        $this->cryptos[] = $crypto;
    }

    /**
     * @param EventStreamInterface $eventStream
     *
     * @return CryptoEventStream
     */
    public function createEncryptingStream(EventStreamInterface $eventStream)
    {
        return new CryptoEventStream(
            $eventStream,
            $this->cryptos,
            $this->encryptionKey,
            CryptoEventStream::ENCRYPT
        );
    }

    /**
     * @param EventStreamInterface $eventStream
     *
     * @return CryptoEventStream
     */
    public function createDecryptingStream(EventStreamInterface $eventStream)
    {
        return new CryptoEventStream(
            $eventStream,
            $this->cryptos,
            $this->encryptionKey,
            CryptoEventStream::DECRYPT
        );
    }
}
