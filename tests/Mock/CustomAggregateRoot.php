<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\Message;

final class CustomAggregateRoot implements CustomAggregateRootContract
{
    private $historyEvents = [];

    private $version = 0;

    public function version(): int
    {
        return $this->version;
    }

    public static function buildFromHistoryEvents(\Iterator $historyEvents): CustomAggregateRootContract
    {
        $self = new self();

        $self->historyEvents = $historyEvents;

        return $self;
    }

    public function getHistoryEvents(): \Iterator
    {
        return $this->historyEvents;
    }

    public function identifier(): string
    {
        // not required for this mock
    }

    /**
     * @return Message[]
     */
    public function getPendingEvents(): array
    {
        // not required for this mock
    }
}
