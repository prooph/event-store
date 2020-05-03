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

/** @psalm-immutable */
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

    /**
     * @param string $loginName
     * @param string $fullName
     * @param array<int, string> $groups
     * @param ?DateTimeImmutable $dateLastUpdated
     * @param bool $disabled
     * @param array<int, RelLink> $links
     */
    private function __construct(
        string $loginName,
        string $fullName,
        array $groups,
        ?DateTimeImmutable $dateLastUpdated,
        bool $disabled,
        array $links)
    {
        $this->loginName = $loginName;
        $this->fullName = $fullName;
        $this->groups = $groups;
        $this->dateLastUpdated = $dateLastUpdated;
        $this->disabled = $disabled;
        /** @psalm-suppress ImpurePropertyAssignment */
        $this->links = $links;
    }

    /** @internal */
    public static function fromArray(array $data): self
    {
        $dateLastUpdated = isset($data['dateLastUpdated'])
            ? DateTime::create($data['dateLastUpdated'])
            : null;

        $links = [];

        if (isset($data['links'])) {
            foreach ($data['links'] as $link) {
                $links[] = new RelLink($link['href'], $link['rel']);
            }
        }

        return new self(
            $data['loginName'],
            $data['fullName'],
            $data['groups'],
            $dateLastUpdated,
            $data['disabled'],
            $links
        );
    }

    /** @psalm-pure */
    public function loginName(): string
    {
        return $this->loginName;
    }

    /** @psalm-pure */
    public function fullName(): string
    {
        return $this->fullName;
    }

    /**
     * @return array<int, string>
     * @psalm-pure
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /** @psalm-pure */
    public function dateLastUpdated(): ?DateTimeImmutable
    {
        return $this->dateLastUpdated;
    }

    /** @psalm-pure */
    public function disabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return array<int, RelLink>
     * @psalm-pure
     */
    public function links(): array
    {
        return $this->links;
    }

    /**
     * @throws RuntimeException if rel not found
     * @psalm-pure
     */
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
