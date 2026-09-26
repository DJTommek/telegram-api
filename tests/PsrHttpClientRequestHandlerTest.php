<?php

namespace unreal4u\TelegramAPI\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\PsrHttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;
use unreal4u\TelegramAPI\Telegram\Methods\SendDocument;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\File;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\ReplyParameters;
use unreal4u\TelegramAPI\TgLog;

class PsrHttpClientRequestHandlerTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var array Every request sent through the client, see Middleware::history()
     */
    private $history = [];

    /**
     * @var TgLog
     */
    private $tgLog;

    protected function setUp(): void
    {
        $this->history = [];
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push(Middleware::history($this->history));

        $httpFactory = new HttpFactory();
        $requestHandler = new PsrHttpClientRequestHandler(
            new Client(['handler' => $handlerStack]),
            $httpFactory,
            $httpFactory
        );
        $this->tgLog = new TgLog('TEST-TEST', $requestHandler);
    }

    private function getLastRequest(): RequestInterface
    {
        return end($this->history)['request'];
    }

    public function testPostUrlEncodedRequest()
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents('tests/Mock/MockData/SendMessage.json')));

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = 12341234;
        $sendMessage->text = 'Hello world';

        $result = $this->tgLog->performApiRequest($sendMessage);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertSame('Hello world', $result->text);

        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://api.telegram.org/botTEST-TEST/SendMessage', (string)$request->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        parse_str((string)$request->getBody(), $body);
        $this->assertSame(['chat_id' => '12341234', 'text' => 'Hello world'], $body);
        $this->assertSame((string)strlen((string)$request->getBody()), $request->getHeaderLine('Content-Length'));
    }

    public function testReplyParametersOnlySendFilledInFields()
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents('tests/Mock/MockData/SendMessage.json')));

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = 12341234;
        $sendMessage->text = 'Hello world';
        $sendMessage->reply_parameters = new ReplyParameters();
        $sendMessage->reply_parameters->message_id = 42;

        $this->tgLog->performApiRequest($sendMessage);

        parse_str((string)$this->getLastRequest()->getBody(), $body);
        $this->assertSame('{"message_id":42}', $body['reply_parameters']);
    }

    public function testPostMultipartRequest()
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents('tests/Mock/MockData/SendDocument.json')));

        $sendDocument = new SendDocument();
        $sendDocument->chat_id = 12341234;
        $sendDocument->caption = 'A "quoted" caption';
        $sendDocument->document = new InputFile(__FILE__);

        $this->tgLog->performApiRequest($sendDocument);

        $request = $this->getLastRequest();
        $this->assertSame(1, preg_match(
            '/^multipart\/form-data; boundary="([^"]+)"$/',
            $request->getHeaderLine('Content-Type'),
            $matches
        ));
        $boundary = $matches[1];
        $body = (string)$request->getBody();

        $this->assertSame((string)strlen($body), $request->getHeaderLine('Content-Length'));
        $this->assertStringEndsWith("\r\n--" . $boundary . "--\r\n", $body);
        $this->assertStringContainsString(
            "--$boundary\r\nContent-Disposition: form-data; name=\"chat_id\"\r\n\r\n12341234\r\n",
            $body
        );
        $this->assertStringContainsString(
            "--$boundary\r\nContent-Disposition: form-data; name=\"caption\"\r\n\r\nA \"quoted\" caption\r\n",
            $body
        );
        $this->assertStringContainsString(
            "--$boundary\r\nContent-Disposition: form-data; name=\"document\"; filename=\"PsrHttpClientRequestHandlerTest.php\"\r\n"
            . "Content-Type: application/octet-stream\r\n\r\n" . file_get_contents(__FILE__) . "\r\n",
            $body
        );
    }

    public function testTelegramErrorResponse()
    {
        $this->mockHandler->append(new Response(
            400,
            [],
            file_get_contents('tests/Mock/MockData/GetFile-invalidFileId.json')
        ));

        $getFile = new GetFile();
        $getFile->file_id = 'invalid';

        try {
            $this->tgLog->performApiRequest($getFile);
            $this->fail('A ClientException should have been thrown');
        } catch (ClientException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertSame(400, $e->getError()->error_code);
            $this->assertNotEmpty($e->getError()->description);
        }
    }

    public function testNonTelegramErrorResponse()
    {
        $this->mockHandler->append(new Response(502, [], '<html>Bad Gateway</html>'));

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(502);
        $this->expectExceptionMessage('Bad Gateway');

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = 12341234;
        $sendMessage->text = 'Hello world';

        $this->tgLog->performApiRequest($sendMessage);
    }

    public function testTransportError()
    {
        $this->mockHandler->append(new ConnectException('Could not resolve host', new Request('POST', 'test')));

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = 12341234;
        $sendMessage->text = 'Hello world';

        try {
            $this->tgLog->performApiRequest($sendMessage);
            $this->fail('A ClientException should have been thrown');
        } catch (ClientException $e) {
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
            $this->assertSame('Could not resolve host', $e->getMessage());
        }
    }

    public function testDownloadFile()
    {
        // Beginning of a JPEG file, definitely not JSON
        $contents = str_repeat("\xFF\xD8\xFF\xE0", 16);
        $this->mockHandler->append(new Response(200, ['content-type' => 'image/jpeg'], $contents));

        $file = new File();
        $file->file_path = 'photos/file_1.jpg';

        $document = $this->tgLog->downloadFile($file);

        $request = $this->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.telegram.org/file/botTEST-TEST/photos/file_1.jpg', (string)$request->getUri());
        $this->assertSame('image/jpeg', $document->mime_type);
        $this->assertSame(64, $document->file_size);
        $this->assertSame($contents, (string)$document);
    }
}
