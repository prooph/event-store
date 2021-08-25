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

namespace Prooph\EventStore\Internal;

use Closure;
use Prooph\EventStore\ClientAuthenticationFailedEventArgs;
use Prooph\EventStore\ClientClosedEventArgs;
use Prooph\EventStore\ClientConnectionEventArgs;
use Prooph\EventStore\ClientErrorEventArgs;
use Prooph\EventStore\ClientReconnectingEventArgs;
use Prooph\EventStore\ListenerHandler;
use SplObjectStorage;

class EventHandler
{
    /** @var SplObjectStorage[] */
    private array $handlers;

    public function __construct()
    {
        $this->handlers = [
            'connected' => new SplObjectStorage(),
            'disconnected' => new SplObjectStorage(),
            'reconnecting' => new SplObjectStorage(),
            'closed' => new SplObjectStorage(),
            'errorOccurred' => new SplObjectStorage(),
            'authenticationFailed' => new SplObjectStorage(),
        ];
    }

    public function connected(ClientConnectionEventArgs $args): void
    {
        foreach ($this->handlers['connected'] as $handler) {
            \assert($handler instanceof ListenerHandler);
            $handler->callback()($args);
        }
    }

    public function disconnected(ClientConnectionEventArgs $args): void
    {
        foreach ($this->handlers['disconnected'] as $handler) {
            \assert($handler instanceof ListenerHandler);
            $handler->callback()($args);
        }
    }

    public function reconnecting(ClientReconnectingEventArgs $args): void
    {
        foreach ($this->handlers['reconnecting'] as $handler) {
            \assert($handler instanceof ListenerHandler);
            $handler->callback()($args);
        }
    }

    public function closed(ClientClosedEventArgs $args): void
    {
        foreach ($this->handlers['closed'] as $handler) {
            \assert($handler instanceof ListenerHandler);
            $handler->callback()($args);
        }
    }

    public function errorOccurred(ClientErrorEventArgs $args): void
    {
        foreach ($this->handlers['errorOccurred'] as $handler) {
            \assert($handler instanceof ListenerHandler);
            $handler->callback()($args);
        }
    }

    public function authenticationFailed(ClientAuthenticationFailedEventArgs $args): void
    {
        foreach ($this->handlers['authenticationFailed'] as $handler) {
            \assert($handler instanceof ListenerHandler);
            $handler->callback()($args);
        }
    }

    public function whenConnected(Closure $handler): ListenerHandler
    {
        return $this->attach($handler, 'connected');
    }

    public function whenDisconnected(Closure $handler): ListenerHandler
    {
        return $this->attach($handler, 'disconnected');
    }

    public function whenReconnecting(Closure $handler): ListenerHandler
    {
        return $this->attach($handler, 'reconnecting');
    }

    public function whenClosed(Closure $handler): ListenerHandler
    {
        return $this->attach($handler, 'closed');
    }

    public function whenErrorOccurred(Closure $handler): ListenerHandler
    {
        return $this->attach($handler, 'errorOccurred');
    }

    public function whenAuthenticationFailed(Closure $handler): ListenerHandler
    {
        return $this->attach($handler, 'authenticationFailed');
    }

    public function detach(ListenerHandler $handler): void
    {
        foreach ($this->handlers as $storage) {
            $storage->detach($handler);
        }
    }

    private function attach(Closure $handler, string $eventName): ListenerHandler
    {
        $handler = new ListenerHandler($handler);

        $this->handlers[$eventName]->attach($handler);

        return $handler;
    }
}
