<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use Prooph\Common\Messaging\Message;

interface Projection extends Query
{
    public function getName(): string;

    public function emit(Message $event): void;

    public function linkTo(string $streamName, Message $event): void;

    public function delete(bool $deleteEmittedEvents): void;

    public function run(bool $keepRunning = true): void;
}
