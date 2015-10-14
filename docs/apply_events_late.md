# Applying Events Late

Since prooph/event-store v6 the "apply events late" feature is added to the [AggregateRepository](../src/Aggregate/AggregateRepository.php) and dependent components.

## But what does that mean?

Well, the answer is not that simple. Let's start with the technical implementation first and then hook over to
the pros and cons.

The AggregateRepository got an additional task in v6.
Recorded domain events which are going to be added to the *event stream* are queued by the repository internally.
The repository listens on the `EventStore::commit.post` action event, goes over the queued events and invokes
`AggregateTranslator::applyPendingStreamEvents`. The AggregateTranslator is then responsible for applying the events to
the referenced aggregate root.

Looking at other event sourcing implementations you may find it confusing that the repository applies the pending events
and NOT the aggregate root itself when recording them.

## So why are events first applied after transaction commit?

The answer is part of the question. Imagine you set up a long-running process and keep aggregate roots in-memory between
different actions. Everything would work fine until the event store encounters a problem during persisting events.
The transaction would be rolled back and events would not be persisted but they would already be applied to a
aggregate root. No chance to rollback the aggregate to the state before the problem. That would cause inconsistency and is
the reason why we've added the *apply events late* feature.

It should not effect your aggregate design that much, but you should keep in mind **that the state of the aggregate is not
immediately updated when recording an event but first when the event store transaction is committed successfully!**

*Note: If you don't like the feature because it influences your design you can still fallback to the old logic.
Just use an AggregateTranslator with a no op applyPendingStreamEvents method and continue to apply events when recording them.
It is your choice!*
