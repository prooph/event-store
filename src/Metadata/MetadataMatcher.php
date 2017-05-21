<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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

    public function withMetadataMatch(
        string $field,
        Operator $operator,
        $value,
        FieldType $fieldType = null
    ): MetadataMatcher {
        $this->validateValue($value);

        if (null === $fieldType) {
            $fieldType = FieldType::METADATA();
        }

        if ($fieldType->is(FieldType::MESSAGE_PROPERTY())
            && ! in_array($field, ['uuid', 'createdAt', 'created_at', 'messageName', 'message_name'], true)
        ) {
            throw new InvalidArgumentException(sprintf('Invalid message property "%s" given', $field));
        }

        $self = clone $this;
        $self->data[] = ['field' => $field, 'operator' => $operator, 'value' => $value, 'fieldType' => $fieldType];

        return $self;
    }

    /**
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    private function validateValue($value): void
    {
        if (is_scalar($value) || is_array($value)) {
            return;
        }

        throw new InvalidArgumentException('A metadata value must have a scalar or array    type.');
    }
}
