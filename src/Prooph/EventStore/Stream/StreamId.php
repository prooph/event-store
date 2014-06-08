<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 06.06.14 - 22:24
 */

namespace Prooph\EventStore\Stream;
use Rhumsaa\Uuid\Uuid;

/**
 * Class StreamId
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StreamId 
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @param $id
     */
    public function __construct($id)
    {
        \Assert\that($id)->notEmpty()->string('StreamId must be a string')->maxLength(200);

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
 