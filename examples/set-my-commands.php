<?php

declare(strict_types=1);

include __DIR__ . '/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SetMyCommands;
use unreal4u\TelegramAPI\Telegram\Types\BotCommand;
use unreal4u\TelegramAPI\Telegram\Types\BotCommandScope;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$method = new SetMyCommands();

$command = new BotCommand();
$command->command = 'help';
$command->description = 'description for command help for default scope';
$method->commands[] = $command;

$command = new BotCommand();
$command->command = 'settings';
$command->description = sprintf('%s icon and settings description for default scope', "\u{2764}");
$method->commands[] = $command;

try {
    $response = $tgLog->performApiRequest($method);
    var_dump('Commands for default scope were set. Use GetMyCommands() to get list of them.');
} catch (\Exception $e) {
    var_dump($e->getTraceAsString());
}
