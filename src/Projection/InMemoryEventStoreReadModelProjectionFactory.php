<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use Prooph\EventStore\EventStore;

final class InMemoryEventStoreReadModelProjectionFactory implements ReadModelProjectionFactory
{
    public function __invoke(
        EventStore $eventStore,
        string $name,
        ReadModel $readModel,
        ProjectionOptions $options = null
    ): ReadModelProjection {
        if (null === $options) {
            $options = new ProjectionOptions();
        }

        return new InMemoryEventStoreReadModelProjection(
            $eventStore,
            $name,
            $readModel,
            $options->cacheSize(),
            $options->persistBlockSize(),
            $options->sleep()
        );
    }
}