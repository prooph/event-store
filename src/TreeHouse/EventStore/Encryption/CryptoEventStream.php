<?php

namespace TreeHouse\EventStore\Encryption;

use TreeHouse\EventStore\Event;
use TreeHouse\EventStore\EventStreamInterface;

class CryptoEventStream extends \IteratorIterator implements EventStreamInterface
{
    const ENCRYPT = 1;
    const DECRYPT = 2;

    /**
     * @var EventStreamInterface
     */
    private $stream;

    /**
     * @var string
     */
    private $encryptionKey;

    /**
     * @var self::DECRYPT|self::ENCRYPT
     */
    private $mode;

    /**
     * @var EventCryptoInterface[]
     */
    private $cryptos = [];

    /**
     * @param EventStreamInterface   $stream
     * @param EventCryptoInterface[] $cryptos
     * @param string                 $encryptionKey
     * @param int                    $mode
     */
    public function __construct(EventStreamInterface $stream, array $cryptos, $encryptionKey, $mode)
    {
        parent::__construct($stream);

        if ($mode !== self::ENCRYPT && $mode !== self::DECRYPT) {
            throw new \InvalidArgumentException('Invalid mode given. Allowed values are `CryptoEventStream::ENCRYPT` and `CryptoEventStream::DECRYPT`.');
        }

        $this->stream = $stream;
        $this->encryptionKey = $encryptionKey;
        $this->mode = $mode;
        $this->cryptos = $cryptos;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return $this->stream->count();
    }

    /**
     * @inheritdoc
     *
     * @return Event
     */
    public function current()
    {
        /** @var Event $event */
        $event = parent::current();

        foreach ($this->cryptos as $crypto) {
            if ($crypto->supports($event->getName())) {
                switch ($this->mode) {
                    case self::DECRYPT:
                        $payload = $crypto->decrypt($event->getPayload(), $event->getPayloadVersion(), $this->encryptionKey);
                        break;
                    case self::ENCRYPT:
                        $payload = $crypto->encrypt($event->getPayload(), $event->getPayloadVersion(), $this->encryptionKey);
                        break;
                }

                $event = new Event(
                    $event->getId(),
                    $event->getName(),
                    $payload,
                    $event->getPayloadVersion(),
                    $event->getVersion(),
                    $event->getDate()
                );
            }
        }

        return $event;
    }
}
