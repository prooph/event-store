<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

class StreamMetadataBuilder
{
    private ?int $maxCount;
    private ?int $maxAge;
    private ?int $truncateBefore;
    private ?int $cacheControl;
    /** @var list<string> */
    private array $aclRead;
    /** @var list<string> */
    private array $aclWrite;
    /** @var list<string> */
    private array $aclDelete;
    /** @var list<string> */
    private array $aclMetaRead;
    /** @var list<string> */
    private array $aclMetaWrite;
    /** @var array<string, mixed> */
    private array $customMetadata;

    /**
     * @param list<string> $aclRead
     * @param list<string> $aclWrite
     * @param list<string> $aclDelete
     * @param list<string> $aclMetaRead
     * @param list<string> $aclMetaWrite
     * @param array<string, mixed> $customMetadata
     */
    public function __construct(
        ?int $maxCount = null,
        ?int $maxAge = null,
        ?int $truncateBefore = null,
        ?int $cacheControl = null,
        array $aclRead = [],
        array $aclWrite = [],
        array $aclDelete = [],
        array $aclMetaRead = [],
        array $aclMetaWrite = [],
        array $customMetadata = []
    ) {
        $this->maxCount = $maxCount;
        $this->maxAge = $maxAge;
        $this->truncateBefore = $truncateBefore;
        $this->cacheControl = $cacheControl;
        $this->aclRead = $aclRead;
        $this->aclWrite = $aclWrite;
        $this->aclDelete = $aclDelete;
        $this->aclMetaRead = $aclMetaRead;
        $this->aclMetaWrite = $aclMetaWrite;
        $this->customMetadata = $customMetadata;
    }

    public function build(): StreamMetadata
    {
        $acl = empty($this->aclRead)
                && empty($this->aclWrite)
                && empty($this->aclDelete)
                && empty($this->aclMetaRead)
                && empty($this->aclMetaWrite)
            ? null
            : new StreamAcl($this->aclRead, $this->aclWrite, $this->aclDelete, $this->aclMetaRead, $this->aclMetaWrite);

        return new StreamMetadata(
            $this->maxCount,
            $this->maxAge,
            $this->truncateBefore,
            $this->cacheControl,
            $acl,
            $this->customMetadata
        );
    }

    /** Sets the maximum number of events allowed in the stream */
    public function setMaxCount(int $maxCount): StreamMetadataBuilder
    {
        if ($maxCount < 0) {
            throw new InvalidArgumentException('Max count must be positive');
        }

        $this->maxCount = $maxCount;

        return $this;
    }

    /** Sets the maximum age of events allowed in the stream */
    public function setMaxAge(int $maxAge): StreamMetadataBuilder
    {
        if ($maxAge < 0) {
            throw new InvalidArgumentException('Max age must be positive');
        }

        $this->maxAge = $maxAge;

        return $this;
    }

    /** Sets the event number from which previous events can be scavenged */
    public function setTruncateBefore(int $truncateBefore): StreamMetadataBuilder
    {
        if ($truncateBefore < 0) {
            throw new InvalidArgumentException('Truncate before must be positive');
        }

        $this->truncateBefore = $truncateBefore;

        return $this;
    }

    /** Sets the amount of time for which the stream head is cachable */
    public function setCacheControl(int $cacheControl): StreamMetadataBuilder
    {
        if ($cacheControl < 0) {
            throw new InvalidArgumentException('CacheControl must be positive');
        }

        $this->cacheControl = $cacheControl;

        return $this;
    }

    /** Sets role names with read permission for the stream */
    public function setReadRoles(string ...$readRole): StreamMetadataBuilder
    {
        $this->aclRead = $readRole;

        return $this;
    }

    /** Sets role names with write permission for the stream */
    public function setWriteRoles(string ...$writeRole): StreamMetadataBuilder
    {
        $this->aclWrite = $writeRole;

        return $this;
    }

    /** Sets role names with delete permission for the stream */
    public function setDeleteRoles(string ...$deleteRole): StreamMetadataBuilder
    {
        $this->aclDelete = $deleteRole;

        return $this;
    }

    /** Sets role names with metadata read permission for the stream */
    public function setMetadataReadRoles(string ...$metaReadRoles): StreamMetadataBuilder
    {
        $this->aclMetaRead = $metaReadRoles;

        return $this;
    }

    /** Sets role names with metadata write permission for the stream */
    public function setMetadataWriteRoles(string ...$metaWriteRoles): StreamMetadataBuilder
    {
        $this->aclMetaWrite = $metaWriteRoles;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setCustomProperty(string $key, $value): StreamMetadataBuilder
    {
        $this->customMetadata[$key] = $value;

        return $this;
    }

    public function removeCustomProperty(string $key): StreamMetadataBuilder
    {
        unset($this->customMetadata[$key]);

        return $this;
    }
}
