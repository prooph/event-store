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

namespace Prooph\EventStore\UserManagement;

use JsonSerializable;
use stdClass;

final class UserCreationInformation implements JsonSerializable
{
    private string $loginName;
    private string $fullName;
    /** @var string[] */
    private array $groups;
    private string $password;

    public function __construct(string $loginName, string $fullName, array $groups, string $password)
    {
        $this->loginName = $loginName;
        $this->fullName = $fullName;
        $this->groups = $groups;
        $this->password = $password;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();
        $object->loginName = $this->loginName;
        $object->fullName = $this->fullName;
        $object->groups = $this->groups;
        $object->password = $this->password;

        return $object;
    }
}
