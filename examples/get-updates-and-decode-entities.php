<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;
use \unreal4u\TelegramAPI\Abstracts\TraversableCustomType;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$getUpdates = new GetUpdates();

// If using this method, send an offset (AKA last known update_id) to avoid getting duplicate update notifications.
#$getUpdates->offset = 328221148;

try {
    /** @var TraversableCustomType $updatesArray */
    $updatesArray = $tgLog->performApiRequest($getUpdates);
    foreach ($updatesArray as $update) {
        $decodedText = $update->message->decodeEntities('HTML'); //or MarkdownV2 or Markdown (abandoned, only for backward compatibility)
        echo $decodedText;
    }
} catch (\Exception $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
