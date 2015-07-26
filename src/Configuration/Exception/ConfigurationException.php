<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Configuration\Exception;

use Prooph\EventStore\Exception\EventStoreException;

/**
 * ConfigurationException
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 */
class ConfigurationException extends \RuntimeException implements EventStoreException
{
    /**
     * @param string $msg
     * @return ConfigurationException
     */
    public static function configurationError($msg)
    {
        return new self('[Configuration Error] ' . $msg . "\n");
    }
}
