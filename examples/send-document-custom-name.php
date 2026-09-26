<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendDocument;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

// File name shown in Telegram clients has exactly same name as file is saved on system. So if we want to upload
// file but with different name, we have to rename it or in this example, copy it as new name.
// Later, after Telegram request is completed, temporary file can be easily deleted
$tempFilePath = sprintf('%s/binary-test-data/%s/custom-file-name.txt', __DIR__, uniqid());
mkdir(dirname($tempFilePath));
copy(__FILE__, $tempFilePath);

$sendDocument = new SendDocument();
$sendDocument->chat_id = A_USER_CHAT_ID;
$sendDocument->document = new InputFile($tempFilePath);
$sendDocument->parse_mode = 'HTML';
$sendDocument->caption = sprintf('Uploaded file was originaly named as <b>%s</b> but display name in Telegram client is <b>%s</b>', basename(__FILE__), basename($tempFilePath));

try {
    $response = $tgLog->performApiRequest($sendDocument);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
} catch (ClientException $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
} finally {
    // Delete temporary file and directory after upload to Telegram server is completed
    if (@unlink($tempFilePath) && @rmdir(dirname($tempFilePath))) {
        var_dump('Temporary file and folder were deleted.');
    } else {
        var_dump('Error while deleting temporary file or directory:', error_get_last());
    }
}
