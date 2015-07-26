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

use Assert\Assertion;

/**
 * Class StreamName
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StreamName
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        Assertion::string($name, 'StreamName must be a string');
        Assertion::notEmpty($name, 'StreamName must not be empty');
        Assertion::maxLength($name, 200, 'StreamName should not be longer than 200 chars');

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
 