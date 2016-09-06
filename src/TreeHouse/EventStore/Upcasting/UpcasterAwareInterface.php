<?php

namespace TreeHouse\EventStore\Upcasting;

interface UpcasterAwareInterface
{
    /**
     * @param UpcasterInterface $upcaster
     */
    public function setUpcaster(UpcasterInterface $upcaster);
}
