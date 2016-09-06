<?php

namespace TreeHouse\EventStore\Tests;

use TreeHouse\Serialization\SerializableInterface;

class DummyEvent implements SerializableInterface
{
    /**
     * @return array
     */
    public function serialize()
    {
        return [];
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function deserialize($data)
    {
        return new self();
    }
}
