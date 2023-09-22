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

namespace Prooph\EventStore\Transport\Http;

/** @internal */
class HttpMethod
{
    public const Get = 'GET';

    public const Post = 'POST';

    public const Put = 'PUT';

    public const Delete = 'DELETE';

    public const Options = 'OPTIONS';

    public const Head = 'HEAD';

    public const Patch = 'PATCH';
}
