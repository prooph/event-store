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

use Prooph\EventStore\Common\SystemMetadata;
use Prooph\EventStore\Exception\InvalidArgumentException;

class StreamAcl
{
    /**
     * Roles and users permitted to read the stream
     * @var string[]
     */
    private $readRoles;

    /**
     * Roles and users permitted to write to the stream
     * @var string[]
     */
    private $writeRoles;

    /**
     * Roles and users permitted to delete the stream
     * @var string[]
     */
    private $deleteRoles;

    /**
     * Roles and users permitted to read stream metadata
     * @var string[]
     */
    private $metaReadRoles;

    /**
     * Roles and users permitted to write stream metadata
     * @var string[]
     */
    private $metaWriteRoles;

    public function __construct(
        array $readRoles = [],
        array $writeRoles = [],
        array $deleteRoles = [],
        array $metaReadRoles = [],
        array $metaWriteRoles = []
    ) {
        $check = function (array $data): void {
            foreach ($data as $value) {
                if (! \is_string($value) || '' === $value) {
                    throw new InvalidArgumentException('Invalid roles given, expected an array of strings');
                }
            }
        };

        $check($readRoles);
        $check($writeRoles);
        $check($deleteRoles);
        $check($metaReadRoles);
        $check($metaWriteRoles);

        $this->readRoles = $readRoles;
        $this->writeRoles = $writeRoles;
        $this->deleteRoles = $deleteRoles;
        $this->metaReadRoles = $metaReadRoles;
        $this->metaWriteRoles = $metaWriteRoles;
    }

    /**
     * @return string[]
     */
    public function readRoles(): array
    {
        return $this->readRoles;
    }

    /**
     * @return string[]
     */
    public function writeRoles(): array
    {
        return $this->writeRoles;
    }

    /**
     * @return string[]
     */
    public function deleteRoles(): array
    {
        return $this->deleteRoles;
    }

    /**
     * @return string[]
     */
    public function metaReadRoles(): array
    {
        return $this->metaReadRoles;
    }

    /**
     * @return string[]
     */
    public function metaWriteRoles(): array
    {
        return $this->metaWriteRoles;
    }

    public function toArray(): array
    {
        $data = [];

        if (! empty($this->readRoles)) {
            $data[SystemMetadata::ACL_READ] = $this->exportRoles($this->readRoles);
        }

        if (! empty($this->writeRoles)) {
            $data[SystemMetadata::ACL_WRITE] = $this->exportRoles($this->writeRoles);
        }

        if (! empty($this->deleteRoles)) {
            $data[SystemMetadata::ACL_DELETE] = $this->exportRoles($this->deleteRoles);
        }

        if (! empty($this->metaReadRoles)) {
            $data[SystemMetadata::ACL_META_READ] = $this->exportRoles($this->metaReadRoles);
        }

        if (! empty($this->metaWriteRoles)) {
            $data[SystemMetadata::ACL_META_WRITE] = $this->exportRoles($this->metaWriteRoles);
        }

        return $data;
    }

    public static function fromArray(array $data): StreamAcl
    {
        return new self(
            (array) ($data[SystemMetadata::ACL_READ] ?? []),
            (array) ($data[SystemMetadata::ACL_WRITE] ?? []),
            (array) ($data[SystemMetadata::ACL_DELETE] ?? []),
            (array) ($data[SystemMetadata::ACL_META_READ] ?? []),
            (array) ($data[SystemMetadata::ACL_META_WRITE] ?? [])
        );
    }

    private function exportRoles(?array $roles)
    {
        if (null === $roles
            || empty($roles)
        ) {
            return null;
        }

        if (\count($roles) === 1) {
            return $roles[0];
        }

        return $roles;
    }
}
