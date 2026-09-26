<?php

declare(strict_types = 1);

/**
 * Uploads several files to Telegram at the same time instead of one after another.
 *
 * PSR-18 clients are blocking by design, but a fiber-aware client (like amphp/http-client) only suspends the fiber it
 * is called from. Run every request in its own fiber and they are all in flight at the same time, without any changes
 * to TgLog or the rest of this library.
 *
 * Requirements: PHP 8.1+ and `composer require amphp/http-client-psr7`
 */

include __DIR__.'/basics.php';

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Interceptor\ModifyRequest;
use Amp\Http\Client\Psr7\PsrAdapter;
use Amp\Http\Client\Psr7\PsrHttpClient;
use Amp\Http\Client\Request;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendDocument;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\TgLog;
use function Amp\async;
use function Amp\Future\await;

if (!class_exists(PsrHttpClient::class)) {
    exit('This example needs a fiber-aware PSR-18 client, install one with: composer require amphp/http-client-psr7' . PHP_EOL);
}

$amphpClient = (new HttpClientBuilder())
    // Amp's default of 10 seconds per request is too short for bigger uploads
    ->intercept(new ModifyRequest(static function (Request $request): Request {
        $request->setTransferTimeout(120);
        $request->setInactivityTimeout(120);
        return $request;
    }))
    ->build();
$httpFactory = new HttpFactory();
$psrClient = new PsrHttpClient($amphpClient, new PsrAdapter($httpFactory, $httpFactory));

// A single TgLog can be shared by all fibers
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler($psrClient, $httpFactory, $httpFactory));

$files = array_filter(glob(__DIR__ . '/binary-test-data/*'), 'is_file');
$start = microtime(true);

$futures = [];
foreach ($files as $path) {
    // async() starts the fiber right away, the loop continues as soon as the upload waits for the network
    $futures[$path] = async(static function () use ($tgLog, $path, $start): ?string {
        $sendDocument = new SendDocument();
        $sendDocument->chat_id = A_USER_CHAT_ID;
        $sendDocument->document = new InputFile($path);
        $sendDocument->caption = basename($path);

        try {
            /** @var Message $message */
            $message = $tgLog->performApiRequest($sendDocument);
        } catch (\Exception $exception) {
            // Handle errors per file, so that one failed upload doesn't affect the others
            printf('[%5.2fs] %s failed: %s' . PHP_EOL, microtime(true) - $start, basename($path), $exception->getMessage());
            return null;
        }

        // Telegram may convert some files (e.g. audio), so pick whatever it made of our document
        $fileId = ($message->document ?? $message->audio ?? $message->video ?? $message->voice)->file_id ?? null;
        printf('[%5.2fs] %s uploaded' . PHP_EOL, microtime(true) - $start, basename($path));

        return $fileId;
    });
}

// Waits until all fibers are done, the order of the results matches the order of $futures
$fileIds = array_filter(await($futures));

printf('Uploaded %d of %d files in %.2f seconds' . PHP_EOL, count($fileIds), count($files), microtime(true) - $start);
if ($fileIds !== []) {
    echo 'Download them again with: php async-download-files.php ' . implode(' ', $fileIds) . PHP_EOL;
}
