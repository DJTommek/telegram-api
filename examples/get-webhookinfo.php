<?php

declare(strict_types=1);

include __DIR__ . '/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetWebhookInfo;
use unreal4u\TelegramAPI\Telegram\Types\WebhookInfo;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$webHookInfo = new GetWebhookInfo();

try {
    /** @var WebhookInfo $info */
    $info = $tgLog->performApiRequest($webHookInfo);
    echo '<pre>';
    var_dump($info);
    echo '</pre>';
} catch (\Exception $e) {
    var_dump($e);
}
