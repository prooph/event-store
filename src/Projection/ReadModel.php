<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

interface ReadModel
{
    public function init(): void;

    public function isInitialized(): bool;

    public function reset(): void;

    public function delete(): void;

    /**
     * @param mixed $operation
     */
    public function stack($operation): void;

    public function persistStack(): void;
}
