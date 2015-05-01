<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.04.14 - 20:51
 */

namespace Prooph\EventStore\PersistenceEvent;

use Prooph\Common\Event\ZF2\Zf2ActionEvent;
use Prooph\EventStore\EventStore;

/**
 * Class PreCommitEvent
 *
 * @package Prooph\EventStore\PersistenceEvent
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PreCommitEvent extends Zf2ActionEvent
{
    /**
     * @return EventStore
     */
    public function getEventStore()
    {
        return $this->getTarget();
    }
}
 