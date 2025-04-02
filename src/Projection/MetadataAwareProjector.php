<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2025 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2025 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use Prooph\EventStore\Metadata\MetadataMatcher;

interface MetadataAwareProjector extends Projector
{
    public function withMetadataMatcher(?MetadataMatcher $metadataMatcher = null): MetadataAwareProjector;
}
