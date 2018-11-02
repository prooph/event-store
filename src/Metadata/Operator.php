<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Metadata;

use MabeEnum\Enum;

/**
 * @method static Operator EQUALS()
 * @method static Operator GREATER_THAN()
 * @method static Operator GREATER_THAN_EQUALS()
 * @method static Operator IN()
 * @method static Operator LOWER_THAN()
 * @method static Operator LOWER_THAN_EQUALS()
 * @method static Operator NOT_EQUALS()
 * @method static Operator NOT_IN()
 * @method static Operator REGEX()
 */
class Operator extends Enum
{
    public const EQUALS = '=';
    public const GREATER_THAN = '>';
    public const GREATER_THAN_EQUALS = '>=';
    public const IN = 'in';
    public const LOWER_THAN = '<';
    public const LOWER_THAN_EQUALS = '<=';
    public const NOT_EQUALS = '!=';
    public const NOT_IN = 'nin';
    public const REGEX = 'regex';
}
