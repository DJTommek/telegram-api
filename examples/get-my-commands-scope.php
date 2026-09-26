<?php

declare(strict_types=1);

include __DIR__ . '/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetMyCommands;
use unreal4u\TelegramAPI\Telegram\Types\BotCommand;
use unreal4u\TelegramAPI\Telegram\Types\BotCommandScope;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$scopeType = 'all_private_chats';

$method = new GetMyCommands();
$method->scope = new BotCommandScope();
$method->scope->type = $scopeType;

try {
    $response = $tgLog->performApiRequest($method);
    var_dump(sprintf('There are %d command(s) for scope "%s"', count($response->data), $scopeType));
    foreach($response->data as $command) {
        /** @var $command BotCommand */
        printf('/%s - %s' . PHP_EOL, $command->command, $command->description);
    }
} catch (\Exception $e) {
    var_dump($e->getTraceAsString());
}
