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

/** @internal */
class UserUpdateInformation implements JsonSerializable
{
    private string $fullName;
    /** @var string[] */
    private array $groups;

    public function __construct(string $fullName, array $groups)
    {
        $this->fullName = $fullName;
        $this->groups = $groups;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();
        $object->fullName = $this->fullName;
        $object->groups = $this->groups;

        return $object;
    }
}
