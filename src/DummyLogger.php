<?php

declare(strict_types=1);

namespace unreal4u\TelegramAPI;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Logger that does nothing.
 */
class DummyLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = array()): void
    {
    }
}
