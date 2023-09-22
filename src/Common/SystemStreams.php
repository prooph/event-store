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

namespace Prooph\EventStore\Common;

class SystemStreams
{
    public const PersistentSubscriptionConfig = '$persistentSubscriptionConfig';

    public const AllStream = '$all';

    public const StreamsStream = '$streams';

    public const SettingsStream = '$settings';

    public const StatsStreamPrefix = '$stats';

    public const ScavangeStream = '$scavenges';

    public static function metastreamOf(string $streamId): string
    {
        return '$$' . $streamId;
    }

    public static function isMetastream(string $streamId): bool
    {
        return \strlen($streamId) > 1 && \substr($streamId, 0, 2) === '$$';
    }

    public static function originalStreamOf(string $metastreamId): string
    {
        return \substr($metastreamId, 2);
    }

    public static function isSystemStream(string $streamId): bool
    {
        return \strlen($streamId) !== 0 && $streamId[0] === '$';
    }
}
