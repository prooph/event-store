<?php

namespace TreeHouse\EventStore\Tests;

use TreeHouse\Serialization\SerializableInterface;

class AnythingEvent implements SerializableInterface
{
    protected $array;

    /**
     * AnythingEvent constructor.
     *
     * @param $array
     */
    public function __construct($array = [])
    {
        $this->array = $array;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return $this->array;
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function deserialize($data)
    {
        return new self($data);
    }
}
