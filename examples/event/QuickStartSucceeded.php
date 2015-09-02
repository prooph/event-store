<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/23/15 - 12:26 AM
 */

namespace Example\Event;

use Assert\Assertion;
use Prooph\Common\Messaging\DomainEvent;

/**
 * Class QuickStartSucceeded
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
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
