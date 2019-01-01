<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
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
use Prooph\EventStore\Exception\InvalidArgumentException;
use stdClass;

class SystemSettings implements JsonSerializable
{
    /**
     * Default access control list for new user streams.
     * @var StreamAcl
     */
    private $userStreamAcl;

    /**
     * Default access control list for new system streams.
     * @var StreamAcl
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

    public function __construct(StreamAcl $userStreamAcl, StreamAcl $systemStreamAcl)
    {
        $this->userStreamAcl = $userStreamAcl;
        $this->systemStreamAcl = $systemStreamAcl;
    }

    public function userStreamAcl(): StreamAcl
    {
        return $this->userStreamAcl;
    }

    public function systemStreamAcl(): StreamAcl
    {
        return $this->systemStreamAcl;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();
        $object->{SystemMetadata::USER_STREAM_ACL} = $this->userStreamAcl->toArray();
        $object->{SystemMetadata::SYSTEM_STREAM_ACL} = $this->systemStreamAcl->toArray();

        return $object;
    }

    public static function createFromArray(array $data): SystemSettings
    {
        if (! isset($data[SystemMetadata::USER_STREAM_ACL])) {
            throw new InvalidArgumentException(SystemMetadata::USER_STREAM_ACL . ' is missing');
        }

        if (! isset($data[SystemMetadata::SYSTEM_STREAM_ACL])) {
            throw new InvalidArgumentException(SystemMetadata::SYSTEM_STREAM_ACL . ' is missing');
        }

        return new self(
            StreamAcl::fromArray($data[SystemMetadata::USER_STREAM_ACL]),
            StreamAcl::fromArray($data[SystemMetadata::SYSTEM_STREAM_ACL])
        );
    }
}
