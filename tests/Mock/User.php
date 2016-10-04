<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\Message;
use Ramsey\Uuid\Uuid;

/**
 * Class User
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
class User
{
    /**
     * @var Uuid
     */
    private $userId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var DomainEvent[]
     */
    private $recordedEvents;

    /**
     * @var int
     */
    private $version = 0;

    public static function create(string $name, string $email) : User
    {
        $self = new self();

        $self->recordThat(UserCreated::with(
            [
                'user_id' => Uuid::uuid4()->toString(),
                'name' => $name,
                'email' => $email,
            ],
            $self->nextVersion()
        ));

        return $self;
    }

    public static function reconstituteFromHistory(\Iterator $historyEvents) : User
    {
        $self = new self();

        $self->replay($historyEvents);

        return $self;
    }

    private function __construct()
    {
    }

    public function getVersion() : int
    {
        return $this->version;
    }

    public function getId() : Uuid
    {
        return $this->userId;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function email() : string
    {
        return $this->email;
    }

    public function changeName(string $newName)
    {
        $this->recordThat(UsernameChanged::with(
            [
                'old_name' => $this->name,
                'new_name' => $newName
            ],
            $this->nextVersion()
        ));
    }

    private function recordThat(TestDomainEvent $domainEvent) : void
    {
        $this->version += 1;
        $this->recordedEvents[] = $domainEvent;
        $this->apply($domainEvent);
    }

    public function apply(TestDomainEvent $event) : void
    {
        if ($event instanceof UserCreated) {
            $this->whenUserCreated($event);
        }

        if ($event instanceof UsernameChanged) {
            $this->whenUsernameChanged($event);
        }
    }

    private function whenUserCreated(UserCreated $userCreated) : void
    {
        $payload = $userCreated->payload();

        $this->userId = Uuid::fromString($payload['user_id']);
        $this->name   = $payload['name'];
        $this->email  = $payload['email'];
    }

    private function whenUsernameChanged(UsernameChanged $usernameChanged) : void
    {
        $this->name = $usernameChanged->payload()['new_name'];
    }

    public function popRecordedEvents() : \Iterator
    {
        $recordedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $recordedEvents;
    }

    /**
     * @param DomainEvent[] $streamEvents
     */
    public function replay(\Iterator $streamEvents) : void
    {
        foreach ($streamEvents as $streamEvent) {
            $this->apply($streamEvent);
            $this->version = $streamEvent->version();
        }
    }

    private function nextVersion() : int
    {
        return $this->version + 1;
    }
}
