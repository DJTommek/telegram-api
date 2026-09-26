<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendAudio;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$sendAudio = new SendAudio();
$sendAudio->chat_id = A_USER_CHAT_ID;
$sendAudio->audio = new InputFile('binary-test-data/ICQ-uh-oh.mp3');
$sendAudio->title = 'The famous ICQ new message alert';
$sendAudio->thumbnail = new InputFile(__DIR__ . '/binary-test-data/logo-php7-telegram-bot-api-thumbnail.jpg');

try {
    $response = $tgLog->performApiRequest($sendAudio);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
} catch (\Exception $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
