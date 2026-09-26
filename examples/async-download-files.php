<?php

declare(strict_types = 1);

/**
 * Downloads several files from Telegram at the same time instead of one after another.
 *
 * Every file is handled in its own fiber: first GetFile is called to get its path, then the file itself is downloaded.
 * Both steps are written as plain blocking code, a fiber-aware PSR-18 client makes sure the other fibers keep running
 * while one of them waits for the network.
 *
 * Requirements: PHP 8.1+ and `composer require amphp/http-client-psr7`
 *
 * Usage: php async-download-files.php [file_id ...]
 * Without arguments A_FILE_ID from conf.php is downloaded, async-send-files.php prints some file ids you can use.
 */

include __DIR__.'/basics.php';

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Interceptor\ModifyRequest;
use Amp\Http\Client\Psr7\PsrAdapter;
use Amp\Http\Client\Psr7\PsrHttpClient;
use Amp\Http\Client\Request;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;
use unreal4u\TelegramAPI\Telegram\Types\File;
use unreal4u\TelegramAPI\TgLog;
use function Amp\async;
use function Amp\Future\await;

if (!class_exists(PsrHttpClient::class)) {
    exit('This example needs a fiber-aware PSR-18 client, install one with: composer require amphp/http-client-psr7' . PHP_EOL);
}

$amphpClient = (new HttpClientBuilder())
    ->intercept(new ModifyRequest(static function (Request $request): Request {
        // Amp's default of 10 seconds per request is too short for bigger downloads
        $request->setTransferTimeout(120);
        $request->setInactivityTimeout(120);
        // Bots can download files up to 20 MB, Amp accepts only 10 MB responses by default
        $request->setBodySizeLimit(20 * 1024 * 1024);
        return $request;
    }))
    ->build();
$httpFactory = new HttpFactory();
$psrClient = new PsrHttpClient($amphpClient, new PsrAdapter($httpFactory, $httpFactory));

// A single TgLog can be shared by all fibers
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler($psrClient, $httpFactory, $httpFactory));

$fileIds = array_slice($argv, 1) ?: [A_FILE_ID];
$targetDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'telegram-downloads';
if (!is_dir($targetDirectory)) {
    mkdir($targetDirectory);
}
$start = microtime(true);

$futures = [];
foreach ($fileIds as $fileId) {
    // async() starts the fiber right away, the loop continues as soon as the fiber waits for the network
    $futures[$fileId] = async(static function () use ($tgLog, $fileId, $targetDirectory, $start): bool {
        try {
            $getFile = new GetFile();
            $getFile->file_id = $fileId;
            /** @var File $file */
            $file = $tgLog->performApiRequest($getFile);

            $document = $tgLog->downloadFile($file);
        } catch (\Exception $exception) {
            // Handle errors per file, so that one failed download doesn't affect the others
            printf('[%5.2fs] %s failed: %s' . PHP_EOL, microtime(true) - $start, $fileId, $exception->getMessage());
            return false;
        }

        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . basename($file->file_path);
        file_put_contents($targetPath, $document->contents);
        printf(
            '[%5.2fs] %s (%s, %d bytes) saved to %s' . PHP_EOL,
            microtime(true) - $start,
            $file->file_path,
            $document->mime_type,
            $document->file_size,
            $targetPath
        );

        return true;
    });
}

// Waits until all fibers are done
$downloaded = array_filter(await($futures));

printf('Downloaded %d of %d files in %.2f seconds' . PHP_EOL, count($downloaded), count($fileIds), microtime(true) - $start);
