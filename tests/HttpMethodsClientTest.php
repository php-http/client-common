<?php

namespace Tests\Http\Client\Common;

use Http\Client\Common\HttpMethodsClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class HttpMethodsClientTest extends TestCase
{
    private const URI = '/uri';
    private const HEADER_NAME = 'Content-Type';
    private const HEADER_VALUE = 'text/plain';
    private const BODY = 'body';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var HttpMethodsClient
     */
    private $httpMethodsClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $streamFactory = $requestFactory = new Psr17Factory();
        $this->httpMethodsClient = new HttpMethodsClient($this->httpClient, $requestFactory, $streamFactory);
    }

    public function testGet(): void
    {
        $this->expectSendRequest('get');
    }

    public function testHead(): void
    {
        $this->expectSendRequest('head');
    }

    public function testTrace(): void
    {
        $this->expectSendRequest('trace');
    }

    public function testPost(): void
    {
        $this->expectSendRequest('post', self::BODY);
    }

    public function testPut(): void
    {
        $this->expectSendRequest('put', self::BODY);
    }

    public function testPatch(): void
    {
        $this->expectSendRequest('patch', self::BODY);
    }

    public function testDelete(): void
    {
        $this->expectSendRequest('delete', self::BODY);
    }

    public function testOptions(): void
    {
        $this->expectSendRequest('options', self::BODY);
    }

    /**
     * Run the actual test.
     *
     * As there is no data provider in phpspec, we keep separate methods to get new mocks for each test.
     */
    private function expectSendRequest(string $method, string $body = null): void
    {
        $response = new Response();
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request) use ($body, $method): bool {
                self::assertSame(strtoupper($method), $request->getMethod());
                self::assertSame(self::URI, (string) $request->getUri());
                self::assertSame([self::HEADER_NAME => [self::HEADER_VALUE]], $request->getHeaders());
                self::assertSame((string) $body, (string) $request->getBody());

                return true;
            }))
            ->willReturn($response)
        ;

        $actualResponse = $this->httpMethodsClient->$method(self::URI, [self::HEADER_NAME => self::HEADER_VALUE], self::BODY);
        $this->assertSame($response, $actualResponse);
    }
}
