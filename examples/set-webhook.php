<?php

declare(strict_types=1);

include __DIR__ . '/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$webhook = new SetWebhook();
$webhook->url = 'https://example.com/';
$webhook->max_connections = 3;
$webhook->allowed_updates = ['message'];
$webhook->drop_pending_updates = true;
$webhook->secret_token = 'some-very-secret-string';

try {
    $response = $tgLog->performApiRequest($webhook);
    echo 'Webhook was successfully set. Run get-webhookinfo.php to get detailed information about webhook from server.';
} catch (\Exception $exception) {
    var_dump($exception->getMessage());
}
