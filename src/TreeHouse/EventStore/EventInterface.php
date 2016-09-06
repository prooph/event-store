<?php

namespace TreeHouse\EventStore;

interface EventInterface
{
    /**
     * Returns aggregate id.
     *
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return mixed
     */
    public function getPayload();

    /**
     * @return int
     */
    public function getPayloadVersion();

    /**
     * @return \DateTime
     */
    public function getDate();

    /**
     * @return int
     */
    public function getVersion();
}
