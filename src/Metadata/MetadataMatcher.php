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

    public function addMetadataMatch(string $key, Operator $operator, $value): void
    {
        $this->validateValue($value);

        $this->data[$key] = ['operator' => $operator, 'value' => $value];
    }

    public function matches(array $metadata): bool
    {
        foreach ($this->data as $key => $value) {
            if (! isset($metadata[$key])) {
                return false;
            }

            $testValue = $this->data[$key]['value'];

            switch ($this->data[$key]['operator']) {
                case Operator::EQUALS():
                    if ($testValue != $value) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN():
                    if ($testValue <= $value) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN_EQUALS():
                    if ($testValue < $value) {
                        return false;
                    };
                    break;
                case Operator::LOWER_THAN():
                    if ($testValue >= $value) {
                        return false;
                    }
                    break;
                case Operator::LOWER_THAN_EQUALS():
                    if ($testValue > $value) {
                        return false;
                    }
                    break;
                default:
                    throw new \UnexpectedValueException('Unknown operator found');
            }
        }

        return true;
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
