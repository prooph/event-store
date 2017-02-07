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

namespace Prooph\EventStore\Upcasting;

use Prooph\Common\Messaging\Message;

abstract class SingleMessageUpcaster implements Upcaster
{
    public function upcast(Message $message): Message
    {
        if (! $this->canUpcast($message)) {
            return $message;
        }

        return $this->doUpcast($message);
    }

    abstract protected function canUpcast(Message $message): bool;

    abstract protected function doUpcast(Message $message): Message;
}
