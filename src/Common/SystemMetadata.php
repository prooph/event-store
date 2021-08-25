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

namespace Prooph\EventStore\Common;

class SystemMetadata
{
    // The definition of the MaxAge value assigned to stream metadata
    // Setting this allows all events older than the limit to be deleted
    public const MAX_AGE = '$maxAge';
    // The definition of the MaxCount value assigned to stream metadata
    // setting this allows all events with a sequence less than current -maxcount to be deleted
    public const MAX_COUNT = '$maxCount';
    // The definition of the Truncate Before value assigned to stream metadata
    // setting this allows all events prior to the integer value to be deleted
    public const TRUNCATE_BEFORE = '$tb';
    // Sets the cache control in seconds for the head of the stream.
    public const CACHE_CONTROL = '$cacheControl';
    // The acl definition in metadata
    public const ACL = '$acl';
    // to read from a stream
    public const ACL_READ = '$r';
    // to write to a stream
    public const ACL_WRITE = '$w';
    // to delete a stream
    public const ACL_DELETE = '$d';
    // to read metadata
    public const ACL_META_READ = '$mr';
    // to write metadata
    public const ACL_META_WRITE = '$mw';
    // The user default acl stream
    public const USER_STREAM_ACL = '$userStreamAcl';
    // the system stream defaults acl stream
    public const SYSTEM_STREAM_ACL = '$systemStreamAcl';
}
