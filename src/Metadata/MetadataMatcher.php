<?php

/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
        $this->validateValue($operator, $value);

        if (null === $fieldType) {
            $fieldType = FieldType::METADATA();
        }

        $self = clone $this;
        $self->data[] = ['field' => $field, 'operator' => $operator, 'value' => $value, 'fieldType' => $fieldType];

        return $self;
    }

    /**
     * @param Operator $operator
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    private function validateValue(Operator $operator, $value): void
    {
        if ($operator->is(Operator::IN()) || $operator->is(Operator::NOT_IN())
        ) {
            if (\is_array($value)) {
                return;
            }

            throw new InvalidArgumentException(\sprintf(
                'Value must be an array for the operator %s.',
                $operator->getName()
            ));
        }

        if ($operator->is(Operator::REGEX()) && ! \is_string($value)) {
            throw new InvalidArgumentException('Value must be a string for the regex operator.');
        }

        if (! \is_scalar($value)) {
            throw new InvalidArgumentException(\sprintf(
                'Value must have a scalar type for the operator %s.',
                $operator->getName()
            ));
        }
    }
}
