<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\Message;
use Rhumsaa\Uuid\Uuid;

/**
 * Class Post
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
class Post
{
    /**
     * @var Uuid
     */
    private $postId;

    /**
     * @var string
     */
    private $text;

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
     * @param string $text
     * @param string $email
     * @return Post
     */
    public static function create($text, $email)
    {
        $self = new self();

        $self->recordThat(PostCreated::with(
            [
                'post_id' => Uuid::uuid4()->toString(),
                'text' => $text,
                'email' => $email,
            ],
            $self->nextVersion()
        ));

        return $self;
    }

    /**
     * @param Message[] $historyEvents
     * @return Post
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
     * @return Uuid
     */
    public function getId()
    {
        return $this->postId;
    }

    public function text()
    {
        return $this->text;
    }

    private function recordThat(TestDomainEvent $domainEvent)
    {
        $this->recordedEvents[] = $domainEvent;
        $this->apply($domainEvent);
    }

    public function apply(TestDomainEvent $event)
    {
        if ($event instanceof PostCreated) {
            $this->whenPostCreated($event);
        }
    }

    private function whenPostCreated(PostCreated $postCreated)
    {
        $payload = $postCreated->payload();

        $this->postId = Uuid::fromString($payload['post_id']);
        $this->name   = $payload['name'];
        $this->email  = $payload['email'];
    }

    private function whenPostnameChanged(PostnameChanged $postnameChanged)
    {
        $this->name = $postnameChanged->payload()['new_name'];
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
    private function replay($streamEvents)
    {
        foreach ($streamEvents as $streamEvent) {
            $this->apply($streamEvent);
            $this->version = $streamEvent->version();
        }
    }

    private function nextVersion()
    {
        return ++$this->version;
    }
}
