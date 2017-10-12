# Upcasting

Imagine `v1` of your application already runs in production. You've worked on great new features the last weeks and
want to deploy `v1.1` but the structure of some domain events changed. The new versions of your aggregates would not be
able to replay `v1` domain events correctly. To solve the issue you can **upcast** your history events.

## How does it work?

Basically you need to write a migration script (much like a normal DB migration script). The script should load all
effected events from the event store and manipulate them to be compliant with version `1.1` of your aggregates.
Then simply replace the original events in the stream with the changed ones.

*Note: The event store offers methods to load events from a stream and add new ones, but it has no method to replace them.
The reason for that is simple: "Upcasting" is something your normal program should **not** have access to.
It is only a way to upgrade your application to the next version, so your upcasting script needs to make use
of low-level functionality provided by the underlying driver for the event store adapter.*

## But how do I avoid conflicts during the upcasting process?

Well, that depends on your infrastructure and deployment strategy. The easiest way is to take your application offline,
perform the upcasting script, deploy the new version of the application, and bring the system online again.

A more complex option with no or very little downtime is to use a special [MessageFactory](https://github.com/prooph/common/blob/master/src/Messaging/MessageFactory.php).
First, make the MessageFactory aware of the differences between `v1` and `v1.1` of your events. Deploy the modified factory together
with version `1.1` of your application. The factory takes care of translating old events into new ones.
Perform the "upcasting" script in the background, and, once it has replaced all old events, you can remove the translation logic
from the factory again and exchange it with the simple factory you used before.

*Note: Each event store adapter allows you to set it up with a custom message factory. Please refer to the adapter documentation of your choice to get more information.*

## Upasting on the fly

Starting in v7 prooph offers an upcasting plugin for the event store. Setup is very easy:

```php
$upcaster = new MyUpcaster();
$plugin = new UpcastingPlugin($upcaster);
$plugin->attachToEventStore($eventStore);
```

So next time you `load` your events, they will get upcasted automatically (but not persisted back to the database).

The upcaster interface is very simple:

```php
interface Upcaster
{
    /**
     * @param Message $message
     * @return array of messages
     */
    public function upcast(Message $message): array;
}
```

Prooph also ships with a `SingleEventUpcaster`, an abstract class to help you create upcasters easily.
Additionally an `UpcasterChain` is provided, so you can combine upcasters easily:

```php
$upcaster1 = new MyUpcaster1();
$upcaster2 = new MyUpcaster2();
$upcaster3 = new MyUpcaster3();

$chain = new UpcasterChain($upcaster1, $upcaster2, $upcaster3);
$plugin = new UpcastingPlugin($chain);
$plugin->attachToEventStore($eventStore);
```
