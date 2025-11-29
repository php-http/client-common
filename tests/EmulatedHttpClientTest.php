<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\EmulatedHttpClient;
use Http\Client\Exception\TransferException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class EmulatedHttpClientTest extends TestCase
{
    public function testImplementsHttpAndAsyncClient(): void
    {
        $async = $this->createMock(HttpAsyncClient::class);
        $client = new EmulatedHttpClient($async);

        $this->assertInstanceOf(HttpClient::class, $client);
        $this->assertInstanceOf(HttpAsyncClient::class, $client);
    }

    public function testSendRequestWaitsOnFulfilledPromise(): void
    {
        $async = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $async->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn(new FulfilledPromise($response));

        $client = new EmulatedHttpClient($async);

        $this->assertSame($response, $client->sendRequest($request));
    }

    public function testSendRequestThrowsOnRejectedPromise(): void
    {
        $async = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $exception = new TransferException('failed');

        $async->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn(new RejectedPromise($exception));

        $client = new EmulatedHttpClient($async);

        $this->expectException(TransferException::class);
        $client->sendRequest($request);
    }

    public function testSendAsyncRequestDelegates(): void
    {
        $async = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $promise = new FulfilledPromise($this->createMock(ResponseInterface::class));

        $async->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn($promise);

        $this->assertSame($promise, (new EmulatedHttpClient($async))->sendAsyncRequest($request));
    }
}
