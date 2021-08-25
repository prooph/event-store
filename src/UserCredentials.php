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

use Prooph\EventStore\Exception\InvalidArgumentException;

/** @psalm-immutable */
class UserCredentials
{
    private string $username;
    private string $password;

    public function __construct(string $username, string $password)
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username cannot be empty');
        }

        if (empty($password)) {
            throw new InvalidArgumentException('Password cannot be empty');
        }

        $this->username = $username;
        $this->password = $password;
    }

    /** @psalm-pure */
    public function username(): string
    {
        return $this->username;
    }

    /** @psalm-pure */
    public function password(): string
    {
        return $this->password;
    }
}
