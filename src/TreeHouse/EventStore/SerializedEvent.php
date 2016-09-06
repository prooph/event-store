<?php

namespace TreeHouse\EventStore;

class SerializedEvent implements EventInterface
{
    /**
     * Aggregate id.
     *
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $payload;

    /**
     * @var int
     */
    protected $payloadVersion;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var int
     */
    protected $version;

    /**
     * @param string    $id             aggregate id
     * @param string    $name
     * @param string    $payload
     * @param int       $payloadVersion
     * @param int       $version
     * @param \DateTime $date
     */
    public function __construct($id, $name, $payload, $payloadVersion, $version, \DateTime $date = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->payload = $payload;
        $this->payloadVersion = $payloadVersion;
        $this->version = $version;
        $this->date = $date ?: new \DateTime();
    }

    /**
     * Returns aggregate id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return int
     */
    public function getPayloadVersion()
    {
        return $this->payloadVersion;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}
