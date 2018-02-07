<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Metadata;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Util\Assertion;

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

    public function enrich(Message $message): Message
    {
        foreach ($this->metadataEnrichers as $metadataEnricher) {
            $message = $metadataEnricher->enrich($message);
        }

        return $message;
    }
}
