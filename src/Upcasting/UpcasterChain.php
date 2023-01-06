<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2023 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2023 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Upcasting;

use Prooph\Common\Messaging\Message;

final class UpcasterChain implements Upcaster
{
    /**
     * @var Upcaster[]
     */
    private array $upcasters;

    public function __construct(Upcaster ...$upcasters)
    {
        $this->upcasters = $upcasters;
    }

    public function upcast(Message $message): array
    {
        $result = [];
        $messages = [$message];

        foreach ($this->upcasters as $upcaster) {
            $result = [];

            foreach ($messages as $message) {
                $result = \array_merge($result, $upcaster->upcast($message));
            }

            $messages = $result;
        }

        return $result;
    }
}
