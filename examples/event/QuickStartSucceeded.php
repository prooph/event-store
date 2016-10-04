<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Example\Event;

use Assert\Assertion;
use Prooph\Common\Messaging\DomainEvent;

/**
 * Class QuickStartSucceeded
 *
 * @author Alexander Miertsch <contact@prooph.de>
 */
final class QuickStartSucceeded extends DomainEvent
{
    /**
     * @var string
     */
    private $text;

    public static function withSuccessMessage($text)
    {
        return new self($text);
    }

    /**
     * @param string $text
     */
    private function __construct($text)
    {
        Assertion::minLength($text, 1, 'Success message must be at least 1 char long');
        $this->text = $text;
        $this->init();
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @inheritdoc
     */
    public function payload()
    {
        return ['text' => $this->text];
    }

    /**
     * @inheritdoc
     */
    protected function setPayload(array $payload)
    {
        $this->text = $payload['text'];
    }
}
