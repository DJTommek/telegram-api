<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
use unreal4u\TelegramAPI\Telegram\Methods\SendVideo;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

// Let the user know we are doing some intensive stuff
$sendChatAction = new SendChatAction();
$sendChatAction->chat_id = A_USER_CHAT_ID;
$sendChatAction->action = 'upload_video';

$sendVideo = new SendVideo();
$sendVideo->chat_id = A_USER_CHAT_ID;
$sendVideo->video = new InputFile(__DIR__ . '/binary-test-data/demo-video.mp4');
$sendVideo->caption = 'Example of a video file sent with Telegram';

try {
    $tgLog->performApiRequest($sendChatAction);
    $response = $tgLog->performApiRequest($sendVideo);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
} catch (\Exception $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
