<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
    public function __construct(string $name)
    {
        Assertion::notEmpty($name, 'StreamName must not be empty');
        Assertion::maxLength($name, 200, 'StreamName should not be longer than 200 chars');

        $this->name = $name;
    }

    public function toString() : string
    {
        return $this->name;
    }

    public function __toString() : string
    {
        return $this->toString();
    }
}
