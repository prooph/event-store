# Replay Event History

One advantage of separating read and write side is that the read model becomes throw-away data.
Don't get us wrong. A performance optimised read model is very important for your application but recorded events
are the only source of truth. The read model can be regenerated from history at any point in time by replaying recorded events.

# Replay vs Load Stream

prooph/event-store offers different methods to load recorded events. They differ in the way a stream is filtered and
in the way the events are ordered. When reconstituting aggregates events need to be ordered by their version in ascending order.

Replaying events to regenerate a read model or create a new projection requires different event ordering.
The read model is maybe interested in domain events of different aggregates and these events should be ordered beginning with the oldest event.
Therefor the event store offers a special `replay` method. Let's look at it in detail:

```php
/**
 * @param StreamName[] $streamNames
 * @param DateTimeInterface|null $since
 * @param null|array $metadatas One metadata array per stream name, same index order is required
 * @return CompositeIterator
 * @throws Exception\InvalidArgumentException
 */
public function replay(array $streamNames, DateTimeInterface $since = null, array $metadatas = null)
{
    /* Assertions are hidden ... */

    $iterators = [];
    foreach ($streamNames as $key => $streamName) {
        $iterators[] = $this->adapter->replay($streamName, $since, $metadatas[$key]);
    }

    return new CompositeIterator($iterators, function (Message $message1 = null, Message $message2) {
        if (null === $message1) {
            return true;
        }
        return $message1->createdAt()->format('U.u') > $message2->createdAt()->format('U.u');
    });
}
```

The replay method is very flexible and powerful. It not only allows you to load all events ordered by date starting
from the oldest event but also gives you the ability to filter certain streams only and start at a specific point in time.
If you provide the third parameter `$metadatas` you need to make sure that it contains a `metadata` array for each stream listed in
the `streamNames` array. Metadata can be used to filter a stream by aggregate type for example.

## Order By Created-At And Aggregate Version

As you can see in the replay method the event store makes use of a special [CompositeIterator](../src/Util/CompositeIterator.php)
with a callback comparing two events by their `createdAt` property. The iterator is initialized with all stream iterators
provided by the event store adapter. Each stream iterator is already ordered by `event.created_at` ascending and `event.version` ascending.
When you loop over the composite iterator in a `foreach` you then get all events of all iterators
ordered from oldest to newest event. Awesome, isn't it?

**Please be aware that the replay method does not quarantee the exact same order of events.**
If two events were recorded at the same time (same microsecond) by two different processes for two different aggregates it can happen
that the replay method returns the events not in the same order as the read model received them originally.
It is not possible to achieve such a precision without adding a lot of complexity to the write process of events,
so the benefit doesn't weight the disadvantages.

Again we only talk about events for different aggregates which were recorded at the exact same microsecond.
The correct event order for a single aggregate is quaranteed as events are also ordered by version.
When handling events async your read model should be able to handle out of order events anyway as most messaging systems
do not quarantee message order either. And if your read model really runs into trouble you can always catch errors and
queue events until missing ones are received. This is a much simpler solution than tackling the problem in the event store.

*Note: Use the replay functionality wisely. It should help you regenerate a read model when data got lost or you have to add a new view.
Use a dedicated event bus for replaying as not all listeners should be informed again. Imagine you have a listener which sends emails.
All emails would be sent again! And please be aware that every `rewind` of the composite iterator may cause the inner iterators to query the database again.
The latter depends on the event store adapter used and on the capabilities of the corresponding database driver.*
