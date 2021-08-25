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
     */
    private ?int $maxCount;
    /**
     * The maximum age in seconds for events allowed in the stream.
     */
    private ?int $maxAge;
    /**
     * The event number from which previous events can be scavenged.
     * This is used to implement soft-deletion of streams.
     */
    private ?int $truncateBefore;
    /**
     * The amount of time in seconds for which the stream head is cachable.
     */
    private ?int $cacheControl;
    /**
     * The access control list for the stream.
     */
    private ?StreamAcl $acl;
    /**
     * key => value pairs of custom metadata
     * @var array<string, mixed>
     */
    private array $customMetadata;

    /**
     * @param array<string, mixed> $customMetadata
     */
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
     * @return array<string, mixed>
     */
    public function customMetadata(): array
    {
        return $this->customMetadata;
    }

    /**
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
            $acl = $this->acl->toArray();

            if (! empty($acl)) {
                $object->{SystemMetadata::ACL} = $acl;
            }
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($this->customMetadata as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedAssignment
     */
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
                /** @var array<string, list<string>> $value */
                $params[SystemMetadata::ACL] = StreamAcl::fromArray($value);
            } else {
                $params['customMetadata'][$key] = $value;
            }
        }

        /** @psalm-suppress MixedArgumentTypeCoercion */
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
