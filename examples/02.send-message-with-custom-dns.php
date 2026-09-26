<?php

declare(strict_types = 1);

/**
 * Transport settings such as DNS are a concern of the HTTP client, not of this library. Guzzle (cURL) can only pick
 * DNS servers when cURL is compiled with c-ares, which is rarely the case, so this example uses amphp/http-client
 * which ships its own DNS resolver. Its requests are just as blocking as Guzzle's, unless you run them in fibers
 * (see async-send-files.php).
 *
 * Requirements: PHP 8.1+ and `composer require amphp/http-client-psr7`
 */

include __DIR__.'/basics.php';

use Amp\Dns\DnsConfig;
use Amp\Dns\Rfc1035StubDnsResolver;
use Amp\Dns\StaticDnsConfigLoader;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Psr7\PsrAdapter;
use Amp\Http\Client\Psr7\PsrHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\TgLog;
use function Amp\Dns\dnsResolver;

if (!class_exists(PsrHttpClient::class)) {
    exit('This example needs amphp/http-client, install it with: composer require amphp/http-client-psr7' . PHP_EOL);
}

// Resolve all hostnames with a custom DNS server instead of the ones configured in the operating system
dnsResolver(new Rfc1035StubDnsResolver(null, new StaticDnsConfigLoader(new DnsConfig(['8.8.4.4:53']))));

$httpFactory = new HttpFactory();
$client = new PsrHttpClient(HttpClientBuilder::buildDefault(), new PsrAdapter($httpFactory, $httpFactory));
$tgLog = new TgLog(BOT_TOKEN, new PsrHttpClientRequestHandler($client, $httpFactory, $httpFactory));

$sendMessage = new SendMessage();
$sendMessage->chat_id = A_USER_CHAT_ID;
$sendMessage->text = 'Hello world!';

$tgLog->performApiRequest($sendMessage);
