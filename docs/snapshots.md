# Snapshots

One of the counter-arguments against Event-Sourcing you might heard about is that replaying events takes to much time to reconstitute an aggregate.
Well, you really need to replay a huge amount of events for a noticeable performance loss compared to using an ORM.
Keep in mind that reading the event stream of an aggregate is a very simple query using an index and NO joins so it is fast as hell.

## But my aggregates record tons of events!
If aggregate reconstitution gets slow you can add an additional layer to the system which
is capable of providing aggregate snapshots. The mechanism is relatively simple:

### Behind The Scenes
- Monitor aggregate streams in the background either by dispatching recorded events async to a monitoring process or
by running a cron job which keeps an eye on the aggregate streams
- Use the `event.version` to check if it is time for the next snapshot, f.e. `if ($event->version() % 500 === 0) {...}`
- Load the aggregate from its repository and pass it to a [snapshot adapter](../README.md#available-snapshot-adapters)

### In The Application
- Set up the [AggregateRepository](../src/Aggregate/AggregateRepository.php) with the same [snapshot adapter](../README.md#available-snapshot-adapters)
used to make snapshots

That's it. The aggregate repository will use the snapshot adapter first to check if it can provide a snapshot for
the requested aggregate. If this is the case the repository retrieves the aggregate from the snapshot adapter and
only loads and replays newer events. The snapshot contains the version of the aggregate at the time
the snapshot was taken. This version is passed to the event store which then only loads events for the aggregate with a
version greater than the snapshot version. This will give you a nice performance again.

