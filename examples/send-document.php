<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;
use unreal4u\TelegramAPI\Telegram\Methods\SendDocument;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$sendDocument = new SendDocument();
$sendDocument->chat_id = A_USER_CHAT_ID;
$sendDocument->document = new InputFile(__FILE__);
$sendDocument->thumbnail = new InputFile(__DIR__ . '/binary-test-data/logo-php7-telegram-bot-api-thumbnail.jpg');

try {
    /** @var \unreal4u\TelegramAPI\Telegram\Types\Message $response */
    $response = $tgLog->performApiRequest($sendDocument);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';

    // Load uploaded document and generate URL to download using bot token
    $getFileDocument = new GetFile();
    $getFileDocument->file_id = $response->document->file_id;
    $fileDocument = $tgLog->performApiRequest($getFileDocument);
    var_dump('Document url: ' . $tgLog->fileUrl($fileDocument));

    // Load uploaded thumbnail and generate URL to download using bot token
    $getFileThumb = new GetFile();
    $getFileThumb->file_id = $response->document->thumbnail->file_id;
    $fileThumb = $tgLog->performApiRequest($getFileThumb);
    var_dump('Thumbnail url: ' . $tgLog->fileUrl($fileThumb));
} catch (ClientException $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
