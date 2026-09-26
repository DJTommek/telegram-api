<?php

declare(strict_types=1);

namespace unreal4u\TelegramAPI;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\InternalFunctionality\TelegramResponse;
use unreal4u\TelegramAPI\Telegram\Types\Custom\UnsuccessfulRequest;

/**
 * Class PsrHttpClientRequestHandler
 *
 * This class implements a request handler based on any PSR-18 compatible HTTP client, together with PSR-17 factories
 * to create the requests.
 *
 * @package unreal4u\TelegramAPI
 */
class PsrHttpClientRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        protected ClientInterface $client,
        protected RequestFactoryInterface $requestFactory,
        protected StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * Performs a GET request against the given URI
     *
     * @param string $uri
     *
     * @return TelegramResponse
     * @throws ClientException
     */
    public function get(string $uri): TelegramResponse
    {
        return $this->sendRequest($this->requestFactory->createRequest('GET', $uri));
    }

    /**
     * Performs a POST request against the given uri, with the given options
     *
     * @param string $uri
     * @param array $formFields an array consisting of request options; known keys include 'headers' and 'body'.
     *
     * @return TelegramResponse
     * @throws ClientException
     */
    public function post(string $uri, array $formFields): TelegramResponse
    {
        $request = $this->requestFactory->createRequest('POST', $uri);

        foreach ($formFields['headers'] ?? [] as $name => $value) {
            $request = $request->withHeader($name, (string)$value);
        }

        if (isset($formFields['body'])) {
            $request = $request->withBody($this->streamFactory->createStream($formFields['body']));
        }

        return $this->sendRequest($request);
    }

    /**
     * Sends the request and converts the response (or any transport error) into our own objects
     *
     * @param RequestInterface $request
     *
     * @return TelegramResponse
     * @throws ClientException
     */
    protected function sendRequest(RequestInterface $request): TelegramResponse
    {
        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ClientException($e->getMessage(), (int)$e->getCode(), $e);
        }

        // Telegram describes failed API calls in a JSON body with "ok": false, TelegramResponse throws on those
        $telegramResponse = new TelegramResponse((string)$response->getBody(), $response->getHeaders());

        // Any other unsuccessful response (e.g. a proxy or gateway error) doesn't come with such a body
        if ($response->getStatusCode() >= 400) {
            $exception = new ClientException();
            $exception->setError(new UnsuccessfulRequest([
                'description' => $response->getReasonPhrase(),
                'error_code' => $response->getStatusCode(),
            ]));
            throw $exception;
        }

        return $telegramResponse;
    }
}
