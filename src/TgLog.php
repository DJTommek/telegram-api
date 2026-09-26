<?php

declare(strict_types=1);

namespace unreal4u\TelegramAPI;

use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Exceptions\MissingMandatoryField;
use unreal4u\TelegramAPI\InternalFunctionality\PostOptionsConstructor;
use unreal4u\TelegramAPI\InternalFunctionality\TelegramDocument;
use unreal4u\TelegramAPI\InternalFunctionality\TelegramResponse;
use unreal4u\TelegramAPI\Telegram\Types\File;

/**
 * The main API which does it all
 */
class TgLog
{
    protected RequestHandlerInterface $requestHandler;

    /**
     * @var PostOptionsConstructor
     */
    protected $formConstructor;

    /**
     * Stores the API URL from Telegram
     */
    private string $apiUrl;

    /**
     * @var string
     */
    protected $methodName = '';

    /**
     * TelegramLog constructor.
     */
    public function __construct(
        private string $botToken,
        RequestHandlerInterface $handler,
        private ?LoggerInterface $logger = null,
    )
    {
        $this->logger = $logger ?? new DummyLogger();

        $this->requestHandler = $handler;
        $this->formConstructor = new PostOptionsConstructor();
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->botToken . '/';
    }

    /**
     * Performs the request to the Telegram servers
     *
     * @param TelegramMethods $method
     *
     * @return TelegramTypes The type depends on the method, see TelegramMethods::bindToObject()
     * @throws MissingMandatoryField
     * @throws ClientException
     */
    public function performApiRequest(TelegramMethods $method): TelegramTypes
    {
        $this->logger->debug('Request for API call, resetting internal values', [\get_class($method)]);
        $this->resetObjectValues();
        $option = $this->formConstructor->constructOptions($method);

        try {
            $response = $this->sendRequestToTelegram($method, $option);
        } catch (ClientException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        return $method::bindToObject($response, $this->logger);
    }

    /**
     * @param File $file
     *
     * @return TelegramDocument
     * @throws ClientException
     */
    public function downloadFile(File $file): TelegramDocument
    {
        $fileUrl = $this->fileUrl($file);
        $this->logger->debug('About to perform request to begin downloading file');

        return new TelegramDocument($this->requestHandler->get($fileUrl));
    }

    /**
     * This is the method that actually makes the call, which can be easily overwritten so that our unit tests can work
     *
     * @param TelegramMethods $method
     * @param array $formData
     *
     * @return TelegramResponse
     * @throws ClientException
     */
    protected function sendRequestToTelegram(TelegramMethods $method, array $formData): TelegramResponse
    {
        $this->logger->debug('About to perform HTTP call to Telegram\'s API');
        return $this->requestHandler->post($this->composeApiMethodUrl($method), $formData);
    }

    /**
     * Resets everything to the default values
     *
     * @return TgLog
     */
    final protected function resetObjectValues(): TgLog
    {
        $this->formConstructor->formType = 'application/x-www-form-urlencoded';
        return $this;
    }

    /**
     * Builds up the URL with which we can work with
     *
     * All methods in the Bot API are case-insensitive.
     * All queries must be made using UTF-8.
     *
     * @see https://core.telegram.org/bots/api#making-requests
     *
     * @param TelegramMethods $call
     * @return string
     */
    protected function composeApiMethodUrl(TelegramMethods $call): string
    {
        $completeClassName = \get_class($call);
        $this->methodName = substr($completeClassName, strrpos($completeClassName, '\\') + 1);
        $this->logger->info('About to perform API request', ['method' => $this->methodName]);

        return $this->apiUrl . $this->methodName;
    }

    /**
     * Generate URL for file download using bot token
     *
     * @param File $file
     * @return string
     */
    public function fileUrl(File $file): string
    {
        return 'https://api.telegram.org/file/bot' . $this->botToken . '/' . $file->file_path;
    }
}
