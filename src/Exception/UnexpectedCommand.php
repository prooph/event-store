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

use Prooph\EventStoreClient\SystemData\TcpCommand;

class UnexpectedCommand extends RuntimeException
{
    public static function with(TcpCommand $actual, ?TcpCommand $expected = null): UnexpectedCommand
    {
        return null === $expected
            ? new self(\sprintf(
                'Unexpected command \'%s\'',
                $actual
            ))
            : new self(\sprintf(
                'Unexpected command \'%s\': expected \'%s\'',
                $actual->name(),
                $expected->name()
            ));
    }
}
