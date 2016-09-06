<?php

namespace TreeHouse\EventStore\Upcasting;

interface VersionAwareInterface
{
    public static function getVersion();
}
