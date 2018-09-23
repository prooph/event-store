# StreamIterator

For `prooph/event-store:7.3` a simple Iterator was returned when calling `$eventStore->load` and `$eventStore->loadReverse`. Getting a count of the total number of matched events in the stream was not possible in a reliably manner. The `iterator_count($streamIterator)` would move the pointer of the Iterator.

The returned iterator instance has been changed from `prooph/event-store:7.4.0` forward to always return an instance of `Prooph\EventStore\StreamIterator\StreamIterator` which implements both `Countable` and `Iterator`.

To get a realtime count of the events while iterating over the stream (even when events are added during iteration) simply use `count($streamIterator)` inside the loop. 

One use case is to provide an accurate progress bar whilst processing event streams. 

```php
$streamIterator = $eventStore->load($streamName);
$progress = new \Symfony\Component\Console\Helper\ProgressBar($outputInterface, count($streamIterator));

foreach ($streamIterator as $event) {
	// do something with $event

	$progress->setMaxSteps(count($streamIterator));
	$progress->advance();
}

$progress->finish();

```

## Notes

When you specifically specify a count argument while loading a stream calling `count($streamIterator)` will return the lesser of *$count* and *the number of matched events*.

```php
$streamIterator = $eventStore->load($streamName, 0, 10)

// with 5 matching events in the store count($streamIterator) would return 5.
// with 15 matching events in the store count($streamIterator) would return 10.
```

Depending on the adapter in use a call to `count($streamIterator)` triggers a potentially expensive operation. Throttling count's might just work.  

```php
foreach ($streamIterator as $index => $event) {
	// do something with $event

	if (0 === $index % 10) {
		$progress->setMaxSteps(count($streamIterator));
		$progress->advance(10);
	}
}
```

