<?php

/*
 * This file is part of the prooph/event-store package.
 * (c) 2014 - 2016 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Metadata;

use Prooph\Common\Messaging\Message;

final class MetadataEnricherAggregate implements MetadataEnricher
{
    /**
     * @var array
     */
    private $metadataEnrichers;

    /**
     * @param array $metadataEnrichers
     */
    public function __construct(array $metadataEnrichers)
    {
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
