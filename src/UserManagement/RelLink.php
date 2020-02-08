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

class RelLink
{
    private string $href;
    private string $rel;

    public function __construct(string $href, string $rel)
    {
        $this->href = $href;
        $this->rel = $rel;
    }

    public function href(): string
    {
        return $this->href;
    }

    public function rel(): string
    {
        return $this->rel;
    }
}
