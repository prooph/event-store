<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Transport\Http;

use Prooph\EventStore\EndPoint;

/** @internal */
class EndpointExtensions
{
    public const HTTP_SCHEMA = 'http';
    public const HTTPS_SCHEMA = 'https';

    public static function rawUrlToHttpUrl(
        EndPoint $endPoint,
        string $schema,
        string $rawUrl = ''
    ): string {
        return self::createHttpUrl(
            $schema,
            $endPoint->host(),
            $endPoint->port(),
            \ltrim($rawUrl, '/')
        );
    }

    public static function formatStringToHttpUrl(
        EndPoint $endPoint,
        string $schema,
        string $formatString,
        string ...$args
    ): string {
        return self::createHttpUrl(
            $schema,
            $endPoint->host(),
            $endPoint->port(),
            \sprintf(\ltrim($formatString, '/'), ...$args)
        );
    }

    private static function createHttpUrl(string $schema, string $host, int $port, string $path): string
    {
        return \sprintf(
            '%s://%s:%d/%s',
            $schema,
            $host,
            $port,
            $path
        );
    }
}
