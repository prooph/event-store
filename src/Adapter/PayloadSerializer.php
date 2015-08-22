<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 8/14/15 - 8:11 PM
 */
namespace Prooph\EventStore\Adapter;

/**
 * Interface PayloadSerializer
 *
 * @package Prooph\EventStore\Adapter
 * @author Alexander Miertsch <alexander.miertsch.extern@sixt.com>
 */
interface PayloadSerializer 
{
    /**
     * @param array $payload
     * @return string
     */
    public function serializePayload(array $payload);

    /**
     * @param string $payloadStr
     * @return array
     */
    public function unserializePayload($payloadStr);
}
