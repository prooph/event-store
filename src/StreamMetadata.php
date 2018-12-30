<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use JsonSerializable;
use Prooph\EventStore\Common\SystemMetadata;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use stdClass;

class StreamMetadata implements JsonSerializable
{
    /**
     * The maximum number of events allowed in the stream.
     * @var int|null
     */
    private $maxCount;

    /**
     * The maximum age in seconds for events allowed in the stream.
     * @var int|null
     */
    private $maxAge;

    /**
     * The event number from which previous events can be scavenged.
     * This is used to implement soft-deletion of streams.
     * @var int|null
     */
    private $truncateBefore;

    /**
     * The amount of time in seconds for which the stream head is cachable.
     * @var int|null
     */
    private $cacheControl;

    /**
     * The access control list for the stream.
     * @var StreamAcl|null
     */
    private $acl;

    /**
     * key => value pairs of custom metadata
     * @var array
     */
    private $customMetadata;

    public function __construct(
        ?int $maxCount = null,
        ?int $maxAge = null,
        ?int $truncateBefore = null,
        ?int $cacheControl = null,
        ?StreamAcl $acl = null,
        array $customMetadata = []
    ) {
        if (null !== $maxCount && $maxCount <= 0) {
            throw new InvalidArgumentException('Max count should be positive value');
        }

        if (null !== $maxAge && $maxAge < 1) {
            throw new InvalidArgumentException('Max age should be positive value');
        }

        if (null !== $truncateBefore && $truncateBefore < 0) {
            throw new InvalidArgumentException('Truncate before should be non-negative value');
        }

        if (null !== $cacheControl && $cacheControl < 1) {
            throw new InvalidArgumentException('Cache control should be positive value');
        }

        foreach ($customMetadata as $key => $value) {
            if (! \is_string($key)) {
                throw new InvalidArgumentException('CustomMetadata key must be a string');
            }
        }

        $this->maxCount = $maxCount;
        $this->maxAge = $maxAge;
        $this->truncateBefore = $truncateBefore;
        $this->cacheControl = $cacheControl;
        $this->acl = $acl;
        $this->customMetadata = $customMetadata;
    }

    public static function create(): StreamMetadataBuilder
    {
        return new StreamMetadataBuilder();
    }

    public function maxCount(): ?int
    {
        return $this->maxCount;
    }

    public function maxAge(): ?int
    {
        return $this->maxAge;
    }

    public function truncateBefore(): ?int
    {
        return $this->truncateBefore;
    }

    public function cacheControl(): ?int
    {
        return $this->cacheControl;
    }

    public function acl(): ?StreamAcl
    {
        return $this->acl;
    }

    /**
     * @return array
     */
    public function customMetadata(): array
    {
        return $this->customMetadata;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getValue(string $key)
    {
        if (! \array_key_exists($key, $this->customMetadata)) {
            throw new RuntimeException('Key ' . $key . ' not found in custom metadata');
        }

        return $this->customMetadata[$key];
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();

        if (null !== $this->maxCount) {
            $object->{SystemMetadata::MAX_COUNT} = $this->maxCount;
        }

        if (null !== $this->maxAge) {
            $object->{SystemMetadata::MAX_AGE} = $this->maxAge;
        }

        if (null !== $this->truncateBefore) {
            $object->{SystemMetadata::TRUNCATE_BEFORE} = $this->truncateBefore;
        }

        if (null !== $this->cacheControl) {
            $object->{SystemMetadata::CACHE_CONTROL} = $this->cacheControl;
        }

        if (null !== $this->acl) {
            $object->{SystemMetadata::ACL} = $this->acl->toArray();
        }

        foreach ($this->customMetadata as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }

    public static function createFromArray(array $data): StreamMetadata
    {
        $internals = [
            SystemMetadata::MAX_COUNT,
            SystemMetadata::MAX_AGE,
            SystemMetadata::TRUNCATE_BEFORE,
            SystemMetadata::CACHE_CONTROL,
        ];

        $params = [];

        foreach ($data as $key => $value) {
            if (\in_array($key, $internals, true)) {
                $params[$key] = $value;
            } elseif ($key === SystemMetadata::ACL) {
                $params[SystemMetadata::ACL] = StreamAcl::fromArray($value);
            } else {
                $params['customMetadata'][$key] = $value;
            }
        }

        return new self(
            $params[SystemMetadata::MAX_COUNT] ?? null,
            $params[SystemMetadata::MAX_AGE] ?? null,
            $params[SystemMetadata::TRUNCATE_BEFORE] ?? null,
            $params[SystemMetadata::CACHE_CONTROL] ?? null,
            $params[SystemMetadata::ACL] ?? null,
            $params['customMetadata'] ?? []
        );
    }
}
