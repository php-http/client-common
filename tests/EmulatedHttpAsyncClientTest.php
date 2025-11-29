<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\EmulatedHttpAsyncClient;
use Http\Client\Exception\TransferException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class EmulatedHttpAsyncClientTest extends TestCase
{
    public function testImplementsHttpAndAsyncClient(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $emulated = new EmulatedHttpAsyncClient($client);

        $this->assertInstanceOf(HttpClient::class, $emulated);
        $this->assertInstanceOf(HttpAsyncClient::class, $emulated);
    }

    public function testSendAsyncRequestWrapsSuccessfulResponse(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);

        $promise = (new EmulatedHttpAsyncClient($client))->sendAsyncRequest($request);
        $this->assertInstanceOf(HttpFulfilledPromise::class, $promise);
        $this->assertSame($response, $promise->wait());
    }

    public function testSendAsyncRequestWrapsFailures(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $exception = new TransferException('failed');
        $client->expects($this->once())->method('sendRequest')->with($request)->willThrowException($exception);

        $promise = (new EmulatedHttpAsyncClient($client))->sendAsyncRequest($request);
        $this->assertInstanceOf(HttpRejectedPromise::class, $promise);
        $this->expectException(\RuntimeException::class);
        $promise->wait();
    }

    public function testSendRequestDelegatesToClient(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);

        $result = (new EmulatedHttpAsyncClient($client))->sendRequest($request);
        $this->assertSame($response, $result);
    }
}
