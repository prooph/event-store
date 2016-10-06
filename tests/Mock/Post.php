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

    public static function create(string $text, string $email): Post
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
    public static function reconstituteFromHistory(array $historyEvents): Post
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
    public function getId(): Uuid
    {
        return $this->postId;
    }

    public function text(): string
    {
        return $this->text;
    }

    private function recordThat(TestDomainEvent $domainEvent): void
    {
        $this->recordedEvents[] = $domainEvent;
        $this->apply($domainEvent);
    }

    public function apply(TestDomainEvent $event): void
    {
        if ($event instanceof PostCreated) {
            $this->whenPostCreated($event);
        }
    }

    private function whenPostCreated(PostCreated $postCreated): void
    {
        $payload = $postCreated->payload();

        $this->postId = Uuid::fromString($payload['post_id']);
        $this->name   = $payload['name'];
        $this->email  = $payload['email'];
    }

    public function popRecordedEvents(): array
    {
        $recordedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $recordedEvents;
    }

    /**
     * @param DomainEvent[] $streamEvents
     */
    private function replay($streamEvents): void
    {
        foreach ($streamEvents as $streamEvent) {
            $this->apply($streamEvent);
            $this->version = $streamEvent->version();
        }
    }

    private function nextVersion(): int
    {
        return ++$this->version;
    }
}
