<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Adapter\Feature;

/**
 * Interface TransactionFeatureInterface
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface TransactionFeatureInterface
{
    public function beginTransaction();
    
    public function commit();
    
    public function rollback();
}
