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

use DateTimeImmutable;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Util\DateTime;

/** @internal */
final class UserDetails
{
    private string $loginName;
    private string $fullName;
    /** @var string[] */
    private array $groups = [];
    private ?DateTimeImmutable $dateLastUpdated = null;
    private bool $disabled;
    /** @var RelLink[] */
    private array $links = [];

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        $details = new self();

        $details->loginName = $data['loginName'];
        $details->fullName = $data['fullName'];
        $details->groups = $data['groups'];
        $details->disabled = $data['disabled'];

        $details->dateLastUpdated = isset($data['dateLastUpdated'])
            ? DateTime::create($data['dateLastUpdated'])
            : null;

        $links = [];
        if (isset($data['links'])) {
            foreach ($data['links'] as $link) {
                $links[] = new RelLink($link['href'], $link['rel']);
            }
        }
        $details->links = $links;

        return $details;
    }

    public function loginName(): string
    {
        return $this->loginName;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    /** @return string[] */
    public function groups(): array
    {
        return $this->groups;
    }

    public function dateLastUpdated(): ?DateTimeImmutable
    {
        return $this->dateLastUpdated;
    }

    public function disabled(): bool
    {
        return $this->disabled;
    }

    /** @return RelLink[] */
    public function links(): array
    {
        return $this->links;
    }

    /** @throws RuntimeException if rel not found */
    public function getRelLink(string $rel): string
    {
        $rel = \strtolower($rel);

        foreach ($this->links() as $link) {
            if (\strtolower($link->rel()) === $rel) {
                return $link->href();
            }
        }

        throw new RuntimeException('Rel not found');
    }
}
