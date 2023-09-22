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

use Prooph\EventStore\EndPoint;

/** @internal */
enum EndpointExtensions : string
{
    case HttpSchema = 'http';
    case HttpsSchema = 'https';

    public static function useHttps(bool $useHttps): self
    {
        return $useHttps ? self::HttpsSchema : self::HttpSchema;
    }

    public static function rawUrlToHttpUrl(
        EndPoint $endPoint,
        self $schema,
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
        self $schema,
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

    private static function createHttpUrl(self $schema, string $host, int $port, string $path): string
    {
        return \sprintf(
            '%s://%s:%d/%s',
            $schema->value,
            $host,
            $port,
            $path
        );
    }
}
