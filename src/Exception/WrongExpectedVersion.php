<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Exception;

class WrongExpectedVersion extends RuntimeException
{
    public static function with(
        string $stream,
        int $expectedVersion,
        ?int $currentVersion = null
    ): WrongExpectedVersion {
        $message = 'Operation failed due to WrongExpectedVersion. Stream: \'%s\', Expected version: %d';

        if (null !== $currentVersion) {
            $message .= ', Current version: %d';
        }

        return new self(\sprintf(
            $message,
            $stream,
            $expectedVersion,
            $currentVersion
        ));
    }
}
