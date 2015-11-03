<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/21/15 - 8:18 PM
 */
namespace ProophTest\EventStore\Mock;

use Prooph\EventStore\Container\Aggregate\AbstractAggregateRepositoryFactory;

final class RepositoryMockFactory extends AbstractAggregateRepositoryFactory
{
    /**
     * Returns the container identifier
     *
     * @return string
     */
    public function containerId()
    {
        return 'repository_mock';
    }
}
