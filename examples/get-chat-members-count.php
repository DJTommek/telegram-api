<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetChatMembersCount;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramAPI\Telegram\Types\Custom\ResultInt;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$getCMC = new GetChatMembersCount();
$getCMC->chat_id = A_GROUP_CHAT_ID;

try {
    /** @var ResultInt $getChatMembersCountResponse */
    $getChatMembersCountResponse = $tgLog->performApiRequest($getCMC);
    echo 'The number of participants in this chat are '.$getChatMembersCountResponse. ' members'.PHP_EOL;
    echo '<pre>';
    var_dump($getChatMembersCountResponse);
    echo '</pre>';
} catch (ClientException $e) {
    var_dump('Captured ClientException', $e->getError());
}
