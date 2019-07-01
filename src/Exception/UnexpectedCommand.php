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

namespace Prooph\EventStore\Exception;

class UnexpectedCommand extends RuntimeException
{
    public static function withName(string $actualCommand): UnexpectedCommand
    {
        return new self(\sprintf(
            'Unexpected command \'%s\'',
            $actualCommand
        ));
    }

    public static function with(string $expectedCommand, string $actualCommand): UnexpectedCommand
    {
        return new self(\sprintf(
            'Unexpected command \'%s\': expected \'%s\'',
            $actualCommand,
            $expectedCommand
        ));
    }
}
