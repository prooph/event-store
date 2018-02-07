<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Exception;

use Prooph\EventStore\StreamName;

final class StreamNotFound extends RuntimeException
{
    public static function with(StreamName $streamName): StreamNotFound
    {
        return new self(
            sprintf(
                'A stream with name %s could not be found',
                $streamName->toString()
            )
        );
    }
}
