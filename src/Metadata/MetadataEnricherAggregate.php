<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Metadata;

use Assert\Assertion;
use Prooph\Common\Messaging\Message;

final class MetadataEnricherAggregate implements MetadataEnricher
{
    /**
     * @var MetadataEnricher[]
     */
    private $metadataEnrichers;

    /**
     * @param MetadataEnricher[] $metadataEnrichers
     */
    public function __construct(array $metadataEnrichers)
    {
        Assertion::allIsInstanceOf($metadataEnrichers, MetadataEnricher::class);

        $this->metadataEnrichers = $metadataEnrichers;
    }

    /**
     * @param Message $message
     *
     * @return Message
     */
    public function enrich(Message $message)
    {
        foreach ($this->metadataEnrichers as $metadataEnricher) {
            $message = $metadataEnricher->enrich($message);
        }

        return $message;
    }
}
