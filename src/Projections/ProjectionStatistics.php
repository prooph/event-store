<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projections;

final class ProjectionStatistics
{
    /** @var list<ProjectionDetails>  */
    private array $projections;

    /**
     * @param list<ProjectionDetails> $projections
     */
    public function __construct(array $projections)
    {
        $this->projections = $projections;
    }

    /**
     * @return list<ProjectionDetails>
     */
    public function projections(): array
    {
        return $this->projections;
    }
}
