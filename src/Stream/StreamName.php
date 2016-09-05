<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Stream;

use Assert\Assertion;

/**
 * Class StreamName
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <contact@prooph.de>
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
