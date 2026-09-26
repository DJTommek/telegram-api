<?php

declare(strict_types=1);

include __DIR__ . '/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendContact;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$sendInvoice = new SendContact();
$sendInvoice->chat_id = A_USER_CHAT_ID;
$sendInvoice->phone_number = '+420123456789';
$sendInvoice->first_name = 'Tomas';
$sendInvoice->last_name = 'Palider';
$sendInvoice->vcard = 'BEGIN:VCARD
VERSION:3.0
EMAIL;TYPE=INTERNET:foo@example.com
END:VCARD';

try {
    $response = $tgLog->performApiRequest($sendInvoice);
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
} catch (\Exception $exception) {
    // Onoes, an exception occurred...
    echo 'Exception ' . get_class($exception) . ' caught, message: ' . $exception->getMessage();
}
