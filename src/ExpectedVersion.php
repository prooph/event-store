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

class ExpectedVersion
{
    // This write should not conflict with anything and should always succeed.
    public const ANY = -2;
    // The stream being written to should not yet exist. If it does exist treat that as a concurrency problem.
    public const NO_STREAM = -1;
    // The stream should exist and should be empty. If it does not exist or is not empty treat that as a concurrency problem.
    public const EMPTY_STREAM = -1;
    // The stream should exist. If it or a metadata stream does not exist treat that as a concurrency problem.
    public const STREAM_EXISTS = -4;
}
