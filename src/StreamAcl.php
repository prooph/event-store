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

namespace Prooph\EventStore;

use Prooph\EventStore\Common\SystemMetadata;

class StreamAcl
{
    /**
     * Roles and users permitted to read the stream
     * @var list<string>
     */
    private array $readRoles;

    /**
     * Roles and users permitted to write to the stream
     * @var list<string>
     */
    private array $writeRoles;

    /**
     * Roles and users permitted to delete the stream
     * @var list<string>
     */
    private array $deleteRoles;

    /**
     * Roles and users permitted to read stream metadata
     * @var list<string>
     */
    private array $metaReadRoles;

    /**
     * Roles and users permitted to write stream metadata
     * @var list<string>
     */
    private array $metaWriteRoles;

    /**
     * @param list<string> $readRoles
     * @param list<string> $writeRoles
     * @param list<string> $deleteRoles
     * @param list<string> $metaReadRoles
     * @param list<string> $metaWriteRoles
     */
    public function __construct(
        array $readRoles = [],
        array $writeRoles = [],
        array $deleteRoles = [],
        array $metaReadRoles = [],
        array $metaWriteRoles = []
    ) {
        $this->readRoles = $readRoles;
        $this->writeRoles = $writeRoles;
        $this->deleteRoles = $deleteRoles;
        $this->metaReadRoles = $metaReadRoles;
        $this->metaWriteRoles = $metaWriteRoles;
    }

    /**
     * @return list<string>
     */
    public function readRoles(): array
    {
        return $this->readRoles;
    }

    /**
     * @return list<string>
     */
    public function writeRoles(): array
    {
        return $this->writeRoles;
    }

    /**
     * @return list<string>
     */
    public function deleteRoles(): array
    {
        return $this->deleteRoles;
    }

    /**
     * @return list<string>
     */
    public function metaReadRoles(): array
    {
        return $this->metaReadRoles;
    }

    /**
     * @return list<string>
     */
    public function metaWriteRoles(): array
    {
        return $this->metaWriteRoles;
    }

    public function toArray(): array
    {
        $data = [];

        if (! empty($this->readRoles)) {
            $data[SystemMetadata::AclRead] = $this->exportRoles($this->readRoles);
        }

        if (! empty($this->writeRoles)) {
            $data[SystemMetadata::AclWrite] = $this->exportRoles($this->writeRoles);
        }

        if (! empty($this->deleteRoles)) {
            $data[SystemMetadata::AclDelete] = $this->exportRoles($this->deleteRoles);
        }

        if (! empty($this->metaReadRoles)) {
            $data[SystemMetadata::AclMetaRead] = $this->exportRoles($this->metaReadRoles);
        }

        if (! empty($this->metaWriteRoles)) {
            $data[SystemMetadata::AclMetaWrite] = $this->exportRoles($this->metaWriteRoles);
        }

        return $data;
    }

    /**
     * @param array<string, list<string>> $data
     */
    public static function fromArray(array $data): StreamAcl
    {
        return new self(...\array_map(
            function (string $role) use ($data): array {
                if (! isset($data[$role])) {
                    return [];
                }

                $roles = $data[$role];

                return ! \is_array($roles) ? [$roles] : $roles;
            },
            [
                SystemMetadata::AclRead,
                SystemMetadata::AclWrite,
                SystemMetadata::AclDelete,
                SystemMetadata::AclMetaRead,
                SystemMetadata::AclMetaWrite,
            ]
        ));
    }

    /**
     * @return list<string>|string|null
     *
     * @psalm-pure
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function exportRoles(?array $roles)
    {
        if (empty($roles)) {
            return null;
        }

        if (\count($roles) === 1) {
            return $roles[0];
        }

        return $roles;
    }
}
