<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\User;
use unreal4u\TelegramAPI\TgLog;

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory));

$getMe = new GetMe();
/** @var User $userInformation */
$userInformation = $tgLog->performApiRequest($getMe);

printf(
    'This bot is called <strong>%s</strong> (username %s) and has id <strong>%d</strong>%s',
    $userInformation->first_name,
    $userInformation->username,
    $userInformation->id,
    PHP_EOL
);

$sendMessage = new SendMessage();
$sendMessage->chat_id = A_USER_CHAT_ID;
$sendMessage->text = 'Hello world to the user... now revamped!';
$tgLog->performApiRequest($sendMessage);

$sendMessage = new SendMessage();
$sendMessage->chat_id = A_GROUP_CHAT_ID;
$sendMessage->text = 'And this is an hello the the group... from scratch :D';
$tgLog->performApiRequest($sendMessage);
