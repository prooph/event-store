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

use JsonSerializable;
use Prooph\EventStore\Common\SystemMetadata;
use Prooph\EventStore\Common\SystemRoles;
use stdClass;

class SystemSettings implements JsonSerializable
{
    /**
     * Default access control list for new user streams.
     * @var StreamAcl|null
     */
    private $userStreamAcl;

    /**
     * Default access control list for new system streams.
     * @var StreamAcl|null
     */
    private $systemStreamAcl;

    public static function default(): SystemSettings
    {
        return new self(
            new StreamAcl(
                [SystemRoles::ALL],
                [SystemRoles::ALL],
                [SystemRoles::ALL],
                [SystemRoles::ALL],
                [SystemRoles::ALL]
            ),
            new StreamAcl(
                [SystemRoles::ALL, SystemRoles::ADMINS],
                [SystemRoles::ALL, SystemRoles::ADMINS],
                [SystemRoles::ALL, SystemRoles::ADMINS],
                [SystemRoles::ALL, SystemRoles::ADMINS],
                [SystemRoles::ALL, SystemRoles::ADMINS]
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
            $object->{SystemMetadata::USER_STREAM_ACL} = $this->userStreamAcl->toArray();
        }

        if ($this->systemStreamAcl) {
            $object->{SystemMetadata::SYSTEM_STREAM_ACL} = $this->systemStreamAcl->toArray();
        }

        return $object;
    }

    public static function createFromArray(array $data): SystemSettings
    {
        return new self(
            isset($data[SystemMetadata::USER_STREAM_ACL])
                ? StreamAcl::fromArray($data[SystemMetadata::USER_STREAM_ACL])
                : null,
            isset($data[SystemMetadata::SYSTEM_STREAM_ACL])
                ? StreamAcl::fromArray($data[SystemMetadata::SYSTEM_STREAM_ACL])
                : null
        );
    }
}
