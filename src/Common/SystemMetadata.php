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

class SystemMetadata
{
    // The definition of the MaxAge value assigned to stream metadata
    // Setting this allows all events older than the limit to be deleted
    public const MaxAge = '$maxAge';

    // The definition of the MaxCount value assigned to stream metadata
    // setting this allows all events with a sequence less than current -maxcount to be deleted
    public const MaxCount = '$maxCount';

    // The definition of the Truncate Before value assigned to stream metadata
    // setting this allows all events prior to the integer value to be deleted
    public const TruncateBefore = '$tb';

    // Sets the cache control in seconds for the head of the stream.
    public const CacheControl = '$cacheControl';

    // The acl definition in metadata
    public const Acl = '$acl';

    // to read from a stream
    public const AclRead = '$r';

    // to write to a stream
    public const AclWrite = '$w';

    // to delete a stream
    public const AclDelete = '$d';

    // to read metadata
    public const AclMetaRead = '$mr';

    // to write metadata
    public const AclMetaWrite = '$mw';

    // The user default acl stream
    public const UserStreamAcl = '$userStreamAcl';

    // the system stream defaults acl stream
    public const SystemStreamAcl = '$systemStreamAcl';
}
