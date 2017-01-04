<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    /**
     * @param array $payload
     * @return string
     */
    public function serializePayload(array $payload)
    {
        return json_encode($payload);
    }

    /**
     * @param string $payloadStr
     * @return array
     */
    public function unserializePayload($payloadStr)
    {
        return json_decode($payloadStr, true);
    }
}
