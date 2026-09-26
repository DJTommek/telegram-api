<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$getFile = new GetFile();
// You can get a file id from an update, webhook or any other message object
$getFile->file_id = A_FILE_ID;

/** @var \unreal4u\TelegramAPI\Telegram\Types\File $file */
$file = $tgLog->performApiRequest($getFile);
$tgDocument = $tgLog->downloadFile($file);

// Offer to download the file immediately
header(sprintf('Content-Type: %s', $tgDocument->mime_type));
header(sprintf('Content-Length: %d', $tgDocument->file_size));
header(sprintf('Content-Disposition: inline; filename="%s"', basename($file->file_path)));
print $tgDocument;
