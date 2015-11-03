<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 9/28/15 - 7:46 PM
 */

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
