<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class FlexibleHttpClientTest extends TestCase
{
    public function testConstructWithInvalidClientThrows(): void
    {
        $this->expectException(\TypeError::class);
        new FlexibleHttpClient(null);
    }

    public function testEmulatesAsyncClientWhenOnlySyncAvailable(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $requestSync = $this->createMock(RequestInterface::class);
        $requestAsync = $this->createMock(RequestInterface::class);
        $responseSync = $this->createMock(ResponseInterface::class);
        $responseAsync = $this->createMock(ResponseInterface::class);

        $httpClient->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnMap([
                [$requestSync, $responseSync],
                [$requestAsync, $responseAsync],
            ]);

        $client = new FlexibleHttpClient($httpClient);

        $this->assertSame($responseSync, $client->sendRequest($requestSync));
        $promise = $client->sendAsyncRequest($requestAsync);
        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertSame($responseAsync, $promise->wait());
    }

    public function testEmulatesSyncClientWhenOnlyAsyncAvailable(): void
    {
        $httpAsyncClient = $this->createMock(HttpAsyncClient::class);
        $requestAsync = $this->createMock(RequestInterface::class);
        $promiseAsync = $this->createMock(Promise::class);
        $requestSync = $this->createMock(RequestInterface::class);
        $responseSync = $this->createMock(ResponseInterface::class);

        $httpAsyncClient->expects($this->exactly(2))
            ->method('sendAsyncRequest')
            ->willReturnMap([
                [$requestAsync, $promiseAsync],
                [$requestSync, new FulfilledPromise($responseSync)],
            ]);

        $promiseAsync->expects($this->never())->method('wait');

        $client = new FlexibleHttpClient($httpAsyncClient);
        $this->assertSame($promiseAsync, $client->sendAsyncRequest($requestAsync));
        $this->assertSame($responseSync, $client->sendRequest($requestSync));
    }

    public function testUsesNativeImplementationsWhenClientSupportsBoth(): void
    {
        $client = new class implements HttpClient, HttpAsyncClient {
            public $syncCalls = 0;
            public $asyncCalls = 0;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                ++$this->syncCalls;

                return new \Nyholm\Psr7\Response();
            }

            public function sendAsyncRequest(RequestInterface $request)
            {
                ++$this->asyncCalls;

                return new FulfilledPromise(new \Nyholm\Psr7\Response());
            }
        };

        $flexible = new FlexibleHttpClient($client);
        $flexible->sendRequest($this->createMock(RequestInterface::class));
        $flexible->sendAsyncRequest($this->createMock(RequestInterface::class));

        $this->assertSame(1, $client->syncCalls);
        $this->assertSame(1, $client->asyncCalls);
    }
}
