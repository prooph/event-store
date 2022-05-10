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

use JsonSerializable;
use Prooph\EventStore\Common\SystemMetadata;
use Prooph\EventStore\Common\SystemRoles;
use stdClass;

class SystemSettings implements JsonSerializable
{
    /**
     * Default access control list for new user streams.
     */
    private ?StreamAcl $userStreamAcl;

    /**
     * Default access control list for new system streams.
     */
    private ?StreamAcl $systemStreamAcl;

    public static function default(): SystemSettings
    {
        return new self(
            new StreamAcl(
                [SystemRoles::All],
                [SystemRoles::All],
                [SystemRoles::All],
                [SystemRoles::All],
                [SystemRoles::All]
            ),
            new StreamAcl(
                [SystemRoles::All, SystemRoles::Admins],
                [SystemRoles::All, SystemRoles::Admins],
                [SystemRoles::All, SystemRoles::Admins],
                [SystemRoles::All, SystemRoles::Admins],
                [SystemRoles::All, SystemRoles::Admins]
            )
        );
    }

    public function __construct(?StreamAcl $userStreamAcl = null, ?StreamAcl $systemStreamAcl = null)
    {
        $this->userStreamAcl = $userStreamAcl;
        $this->systemStreamAcl = $systemStreamAcl;
    }

    public function userStreamAcl(): ?StreamAcl
    {
        return $this->userStreamAcl;
    }

    public function systemStreamAcl(): ?StreamAcl
    {
        return $this->systemStreamAcl;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();

        if ($this->userStreamAcl) {
            $object->{SystemMetadata::UserStreamAcl} = $this->userStreamAcl->toArray();
        }

        if ($this->systemStreamAcl) {
            $object->{SystemMetadata::SystemStreamAcl} = $this->systemStreamAcl->toArray();
        }

        return $object;
    }

    public static function createFromArray(array $data): SystemSettings
    {
        /** @var array<string, array<string, list<string>>> $data */
        return new self(
            isset($data[SystemMetadata::UserStreamAcl])
                ? StreamAcl::fromArray($data[SystemMetadata::UserStreamAcl])
                : null,
            isset($data[SystemMetadata::SystemStreamAcl])
                ? StreamAcl::fromArray($data[SystemMetadata::SystemStreamAcl])
                : null
        );
    }
}
