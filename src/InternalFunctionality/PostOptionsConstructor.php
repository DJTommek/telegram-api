<?php

declare(strict_types=1);

namespace unreal4u\TelegramAPI\InternalFunctionality;

use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\DummyLogger;
use unreal4u\TelegramAPI\Exceptions\MissingMandatoryField;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use function pathinfo;
use function stream_get_contents;
use function strlen;
use function strstr;
use const PATHINFO_BASENAME;

class PostOptionsConstructor
{
    /**
     * With this flag we'll know what type of request to send to Telegram
     *
     * 'application/x-www-form-urlencoded' is the "normal" one, which is simpler and quicker.
     * 'multipart/form-data' should be used only when you upload documents, photos, etc.
     *
     * @var string
     */
    public $formType = 'application/x-www-form-urlencoded';

    /**
     * This is a flag intended to temporarily store the number of local files
     *
     * @var int
     */
    private $numberOfLocalFiles = 0;

    public function __construct(private ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new DummyLogger();
    }

    /**
     * Builds up the form elements to be sent to Telegram
     *
     * @param TelegramMethods $method
     * @return array
     * @throws MissingMandatoryField
     */
    public function constructOptions(TelegramMethods $method): array
    {
        $result = $this->checkIsMultipart($method);

        if (!empty($result)) {
            return $this->constructMultipartOptions(
                $method->export(),
                $result
            );
        }

        $body = http_build_query($method->export(), '', '&');

        return [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Content-Length' => strlen($body),
                'User-Agent' => 'PHP7+ Bot API',
            ],
            'body' => $body
        ];
    }

    /**
     * Check if the given TelegramMethod should be handled as a multipart.
     *
     * @param TelegramMethods $method
     * @return array
     */
    private function checkIsMultipart(TelegramMethods $method): array
    {
        $this->logger->debug('Checking whether to apply special conditions to this request');
        $method->performSpecialConditions();

        $return = [];

        if ($method->hasLocalFiles() === true) {
            $this->logger->debug('About to send a file, so changing request to use multi-part instead');
            // If we are about to send a file, we must use the multipart/form-data way
            $this->formType = 'multipart/form-data';
            foreach ($method->getLocalFiles() as $identifier => $localFile) {
                if ($localFile instanceof InputFile) {
                    $return[$identifier . '~' . $this->numberOfLocalFiles] = [
                        'id' => $this->numberOfLocalFiles,
                        'filename' => basename($localFile->path),
                        'stream' => $localFile->getStream(),
                    ];
                    $this->numberOfLocalFiles++;
                }
            }
        }

        return $return;
    }

    /**
     * Builds up a multipart form-like array for the HTTP client
     *
     * @param array $data The original object in array form
     * @param array $multipartData
     * @return array Returns the actual formdata to be sent
     */
    public function constructMultipartOptions(array $data, array $multipartData): array
    {
        $boundary = bin2hex(random_bytes(16));
        $body = $this->constructBodyForMultipart($boundary, $data, $multipartData);
        $completeRequest = [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary="' . $boundary . '"',
                'Content-Length' => strlen($body)
            ],
            'body' => $body
        ];

        return $completeRequest;
    }

    /**
     * Is able to construct the entire body for a multipart form data
     *
     * @see https://www.rfc-editor.org/rfc/rfc7578
     *
     * @param string $boundary
     * @param array $data
     * @param array $multipartData
     * @return string
     */
    private function constructBodyForMultipart(string $boundary, array $data, array $multipartData): string
    {
        $this->logger->debug('Creating multi-part form array data (complex and expensive)');
        $body = '';

        foreach ($data as $id => $value) {
            if (!$value instanceof InputFile) {
                $body .= $this->buildPart($boundary, (string)$id, (string)$value);
            }
        }

        $body .= $this->appendMediaStream($boundary, $multipartData);

        return $body . '--' . $boundary . "--\r\n";
    }

    /**
     * Scans the actual media and adds it to the multipart form data
     *
     * @param string $boundary
     * @param array $multipartData
     * @return string
     */
    private function appendMediaStream(string $boundary, array $multipartData): string
    {
        $body = '';
        foreach ($multipartData as $identifier => $mediaStream) {
            $body .= $this->buildPart(
                $boundary,
                strstr($identifier, '~', true),
                stream_get_contents($mediaStream['stream']),
                pathinfo($mediaStream['filename'], PATHINFO_BASENAME)
            );
            fclose($mediaStream['stream']);
        }

        return $body;
    }

    /**
     * Builds a single part of a multipart form, including its opening boundary
     *
     * @param string $boundary
     * @param string $name
     * @param string $contents
     * @param string|null $filename When given, the part is sent as a file
     * @return string
     */
    private function buildPart(string $boundary, string $name, string $contents, ?string $filename = null): string
    {
        $disposition = 'form-data; name="' . $this->escapeQuotedString($name) . '"';
        $headers = '';
        if ($filename !== null) {
            $disposition .= '; filename="' . $this->escapeQuotedString($filename) . '"';
            $headers = "Content-Type: application/octet-stream\r\n";
        }

        return '--' . $boundary . "\r\n"
            . 'Content-Disposition: ' . $disposition . "\r\n"
            . $headers
            . "\r\n"
            . $contents . "\r\n";
    }

    private function escapeQuotedString(string $value): string
    {
        return str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', ' ', ' '], $value);
    }
}
