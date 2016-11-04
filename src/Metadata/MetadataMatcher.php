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

namespace Prooph\EventStore\Metadata;

use Prooph\EventStore\Exception\InvalidArgumentException;

class MetadataMatcher
{
    private $data = [];

    public function data(): array
    {
        return $this->data;
    }

    public function withMetadataMatch(string $key, Operator $operator, $value): MetadataMatcher
    {
        $this->validateValue($value);

        $self = clone $this;
        $self->data[$key] = ['operator' => $operator, 'value' => $value];

        return $self;
    }

    /**
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    private function validateValue($value): void
    {
        if (is_scalar($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $v) {
                $this->validateValue($v);
            }
        }

        throw new InvalidArgumentException('Invalid metadata given');
    }
}
