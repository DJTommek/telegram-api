<?php

declare(strict_types = 1);

namespace unreal4u\TelegramAPI;

use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\InternalFunctionality\TelegramResponse;

interface RequestHandlerInterface
{
    /**
     * @param string $uri
     * @param array $formFields an array consisting of request options; known keys include 'headers' and 'body'.
     *
     * @return TelegramResponse
     * @throws ClientException
     */
    public function post(string $uri, array $formFields): TelegramResponse;

    /**
     * @param string $uri
     *
     * @return TelegramResponse
     * @throws ClientException
     */
    public function get(string $uri): TelegramResponse;
}
