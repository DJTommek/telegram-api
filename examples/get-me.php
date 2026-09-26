<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\Abstracts\TelegramTypes;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

try {
    $getMeResponse = $tgLog->performApiRequest(new GetMe());
    var_dump($getMeResponse);
} catch (\Exception $e) {
    var_dump($e);
}
