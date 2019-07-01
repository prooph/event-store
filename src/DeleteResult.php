<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

class DeleteResult
{
    /** @var Position */
    private $logPosition;

    public function __construct(Position $logPosition)
    {
        $this->logPosition = $logPosition;
    }

    public function logPosition(): Position
    {
        return $this->logPosition;
    }
}
