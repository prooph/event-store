<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Exception;

class AccessDenied extends RuntimeException
{
    public static function toAllStream(): AccessDenied
    {
        return new self(\sprintf(
            'Access to stream \'%s\' is denied',
            '$all'
        ));
    }

    public static function toStream(string $stream): AccessDenied
    {
        return new self(\sprintf(
            'Access to stream \'%s\' is denied',
            $stream
        ));
    }
}
