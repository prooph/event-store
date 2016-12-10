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

namespace Prooph\EventStore\Projection;

use Prooph\EventStore\Exception\InvalidArgumentException;

class ProjectionOptions
{
    /**
     * @var int
     */
    protected $cacheSize;

    /**
     * @var int
     */
    protected $persistBlockSize;

    public function __construct(int $cacheSize = 1000, int $persistBlockSize = 1000)
    {
        $this->cacheSize = $cacheSize;
        $this->persistBlockSize = $persistBlockSize;
    }

    public static function fromArray(array $data): ProjectionOptions
    {
        self::validateData($data);

        return new self($data['cache_size'], $data['persist_block_size']);
    }

    public function cacheSize(): int
    {
        return $this->cacheSize;
    }

    public function persistBlockSize(): int
    {
        return $this->persistBlockSize;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected static function validateData(array $data): void
    {
        if (! isset($data['cache_size'])) {
            throw new InvalidArgumentException('cache_size option is missing');
        }

        if (! isset($data['persist_block_size'])) {
            throw new InvalidArgumentException('persist_block_size option is missing');
        }
    }
}
