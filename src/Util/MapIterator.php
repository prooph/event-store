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

namespace Prooph\EventStore\Util;

use IteratorIterator;
use Traversable;

/**
 * Class MapIterator
 *
 * @package Prooph\EventStore\Util
 */
final class MapIterator extends IteratorIterator
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param Traversable $iterator
     * @param callable $callback
     */
    public function __construct(Traversable $iterator, callable $callback)
    {
        parent::__construct($iterator);
        $this->callback = $callback;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $iterator = $this->getInnerIterator();
        $callback = $this->callback;
        return $callback(parent::current(), parent::key(), $iterator);
    }
}
