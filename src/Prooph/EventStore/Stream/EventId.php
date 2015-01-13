<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 06.06.14 - 22:37
 */

namespace Prooph\EventStore\Stream;
use Assert\Assertion;
use Rhumsaa\Uuid\Uuid;

/**
 * Class EventId
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventId 
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @return EventId
     */
    public static function generate()
    {
        return new static(Uuid::uuid4()->toString());
    }

    /**
     * @param $id
     */
    public function __construct($id)
    {
        Assertion::string($id, 'EventId must be a string');
        Assertion::notEmpty($id, 'EventId must not be empty');

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
 