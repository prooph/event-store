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

namespace Prooph\EventStore\UserManagement;

use Prooph\EventStore\Exception\UserCommandFailedException;
use Prooph\EventStore\UserCredentials;

interface UsersManager
{
    public function enable(string $login, ?UserCredentials $userCredentials = null): void;

    public function disable(string $login, ?UserCredentials $userCredentials = null): void;

    /** @throws UserCommandFailedException */
    public function deleteUser(string $login, ?UserCredentials $userCredentials = null): void;

    /** @return UserDetails[] */
    public function listAll(?UserCredentials $userCredentials = null): array;

    public function getCurrentUser(?UserCredentials $userCredentials = null): UserDetails;

    public function getUser(string $login, ?UserCredentials $userCredentials = null): UserDetails;

    /**
     * @param string $login
     * @param string $fullName
     * @param string[] $groups
     * @param string $password
     * @param UserCredentials|null $userCredentials
     *
     * @return void
     */
    public function createUser(
        string $login,
        string $fullName,
        array $groups,
        string $password,
        ?UserCredentials $userCredentials = null
    ): void;

    /**
     * @param string $login
     * @param string $fullName
     * @param string[] $groups
     * @param UserCredentials|null $userCredentials
     *
     * @return void
     */
    public function updateUser(
        string $login,
        string $fullName,
        array $groups,
        ?UserCredentials $userCredentials = null
    ): void;

    public function changePassword(
        string $login,
        string $oldPassword,
        string $newPassword,
        ?UserCredentials $userCredentials = null
    ): void;

    public function resetPassword(
        string $login,
        string $newPassword,
        ?UserCredentials $userCredentials = null
    ): void;
}
