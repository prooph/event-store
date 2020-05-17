<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projections;

final class State
{
    /** @var string|int|float|bool|array */
    private array $payload;

    /** @param string|int|float|bool|array $payload */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /** @return string|int|float|bool|array */
    public function payload()
    {
        return $this->payload;
    }
}
