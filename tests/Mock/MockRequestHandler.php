<?php

namespace unreal4u\TelegramAPI\tests\Mock;

use unreal4u\TelegramAPI\InternalFunctionality\TelegramResponse;
use unreal4u\TelegramAPI\RequestHandlerInterface;

class MockRequestHandler implements RequestHandlerInterface
{
    public function post(string $filename, array $formFields): TelegramResponse
    {
        return new TelegramResponse(file_get_contents($filename));
    }

    public function get(string $filename): TelegramResponse
    {
        return new TelegramResponse(file_get_contents($filename));
    }
}
