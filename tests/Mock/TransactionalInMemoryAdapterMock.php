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

namespace ProophTest\EventStore\Mock;

use Prooph\EventStore\Adapter\Feature\CanHandleTransaction;
use Prooph\EventStore\Adapter\InMemoryAdapter;

/**
 * Class TransactionalInMemoryAdapterMock
 *
 * @package ProophTest\EventStore
 * @author Jefersson Nathan <malukenho@phpse.net>
 */
final class TransactionalInMemoryAdapterMock extends InMemoryAdapter implements CanHandleTransaction
{
    /**
     * {@inheritDoc}
     */
    public function beginTransaction() : void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function commit() : void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function rollback() : void
    {
    }
}
