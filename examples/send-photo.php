<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$sendPhoto = new SendPhoto();
$sendPhoto->chat_id = A_USER_CHAT_ID;
$sendPhoto->photo = new InputFile(__DIR__ . '/binary-test-data/demo-photo.jpg');
$sendPhoto->caption = 'Not sure if sending image or image not arriving';

try {
    $response = $tgLog->performApiRequest($sendPhoto);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
} catch (ClientException $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
