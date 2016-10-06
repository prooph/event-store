<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Adapter\PayloadSerializer;

use Prooph\EventStore\Adapter\PayloadSerializer;

/**
 * Class JsonPayloadSerializer
 *
 * @package Prooph\EventStore\Adapter\PayloadSerializer
 * @author Alexander Miertsch <contact@prooph.de>
 */
final class JsonPayloadSerializer implements PayloadSerializer
{
    public function serializePayload(array $payload): string
    {
        return json_encode($payload);
    }

    public function unserializePayload(string $payloadStr): array
    {
        return json_decode($payloadStr, true);
    }
}
