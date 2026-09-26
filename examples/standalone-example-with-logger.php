<?php

/*
 * NOTE: TgLog accepts any PSR-3 logger, this example uses the simple ConsoleLogger shipped with this package. In your
 * own code you will probably want to pass the logger you already use (Monolog or any other).
 *
 * You can run this example (or any other in this same folder) as follows:
 * vagrant ssh
 * cd /var/www/default/
 * composer update -o
 * php examples/standalone-example-with-logger.php
 */
declare(strict_types = 1);

include __DIR__.'/basics.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetUserProfilePhotos;
use unreal4u\TelegramAPI\Telegram\Types\UserProfilePhotos;
use unreal4u\TelegramAPI\TgLog;

$logger = new \unreal4u\TelegramAPI\ConsoleLogger();

$httpFactory = new HttpFactory();
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler(new Client(), $httpFactory, $httpFactory), $logger);

$userProfilePhotos = new GetUserProfilePhotos();
$userProfilePhotos->user_id = A_USER_ID;

/** @var UserProfilePhotos $photos */
$photos = $tgLog->performApiRequest($userProfilePhotos);
var_dump($photos);
