<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\TgLog;

$logger = new \unreal4u\TelegramAPI\ConsoleLogger();

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory), $logger);
$sendMessage = new SendMessage();
$sendMessage->chat_id = A_USER_CHAT_ID;
$sendMessage->text = 'Hello world, this is a test';

try {
    /** @var Message $message */
    $message = $tgLog->performApiRequest($sendMessage);

    sleep(3);

    $editMessageText = new EditMessageText();
    $editMessageText->message_id = $message->message_id;
    $editMessageText->chat_id = $message->chat->id;
    $editMessageText->text = 'Sorry, this is the correction of an hello world';

    $response = $tgLog->performApiRequest($editMessageText);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
} catch (\Exception $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
