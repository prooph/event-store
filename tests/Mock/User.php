<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/31/14 - 8:00 PM
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\Message;
use Rhumsaa\Uuid\Uuid;

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

    /**
     * @param string $name
     * @param string $email
     * @return User
     */
    public static function create($name, $email)
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

    /**
     * @param Message[] $historyEvents
     * @return User
     */
    public static function reconstituteFromHistory($historyEvents)
    {
        $self = new self();

        $self->replay($historyEvents);

        return $self;
    }

    private function __construct()
    {
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return Uuid
     */
    public function getId()
    {
        return $this->userId;
    }

    public function name()
    {
        return $this->name;
    }

    public function email()
    {
        return $this->email;
    }

    public function changeName($newName)
    {
        $this->recordThat(UsernameChanged::with(
            [
                'old_name' => $this->name,
                'new_name' => $newName
            ],
            $this->nextVersion()
        ));
    }

    private function recordThat(TestDomainEvent $domainEvent)
    {
        $this->version += 1;
        $this->recordedEvents[] = $domainEvent;
        $this->apply($domainEvent);
    }

    public function apply(TestDomainEvent $event)
    {
        if ($event instanceof UserCreated) {
            $this->whenUserCreated($event);
        }

        if ($event instanceof UsernameChanged) {
            $this->whenUsernameChanged($event);
        }
    }

    private function whenUserCreated(UserCreated $userCreated)
    {
        $payload = $userCreated->payload();

        $this->userId = Uuid::fromString($payload['user_id']);
        $this->name   = $payload['name'];
        $this->email  = $payload['email'];
    }

    private function whenUsernameChanged(UsernameChanged $usernameChanged)
    {
        $this->name = $usernameChanged->payload()['new_name'];
    }

    public function popRecordedEvents()
    {
        $recordedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $recordedEvents;
    }

    /**
     * @param DomainEvent[] $streamEvents
     */
    public function replay($streamEvents)
    {
        foreach ($streamEvents as $streamEvent) {
            $this->apply($streamEvent);
            $this->version = $streamEvent->version();
        }
    }

    private function nextVersion()
    {
        return $this->version + 1;
    }
}
