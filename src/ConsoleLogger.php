<?php

declare(strict_types=1);

namespace unreal4u\TelegramAPI;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class ConsoleLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = array()): void
    {
        echo $message . PHP_EOL;
    }
}
