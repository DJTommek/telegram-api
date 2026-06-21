<?php

declare(strict_types = 1);

include __DIR__.'/basics.php';

use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\TgLog;

\Amp\Loop::run(function () {
    $logger = new \unreal4u\TelegramAPI\ConsoleLogger();
    $tgLog = new TgLog(BOT_TOKEN, new \unreal4u\TelegramAPI\HttpClientRequestHandlerAmp(), $logger);

    $sendMessage = new SendMessage();
    $sendMessage->chat_id = A_USER_CHAT_ID;
    $sendMessage->text = 'Hello world!';

    $result = yield $tgLog->performApiRequest($sendMessage);

    var_dump($result);
});
