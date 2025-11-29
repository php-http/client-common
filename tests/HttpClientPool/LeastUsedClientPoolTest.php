<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\HttpClientPool;

use Http\Client\Common\Exception\HttpClientNotFoundException;
use Http\Client\Common\HttpClientPool\HttpClientPoolItem;
use Http\Client\Common\HttpClientPool\LeastUsedClientPool;
use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LeastUsedClientPoolTest extends TestCase
{
    public function testImplementsClients(): void
    {
        $pool = new LeastUsedClientPool();
        $this->assertInstanceOf(HttpClient::class, $pool);
        $this->assertInstanceOf(HttpAsyncClient::class, $pool);
    }

    public function testThrowsWhenNoClientAvailable(): void
    {
        $pool = new LeastUsedClientPool();
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(HttpClientNotFoundException::class);
        $pool->sendRequest($request);
    }

    public function testSendRequestUsesLeastBusyClient(): void
    {
        $pool = new LeastUsedClientPool();
        $client1 = $this->createMock(HttpClientPoolItem::class);
        $client2 = $this->createMock(HttpClientPoolItem::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $pool->addHttpClient($client1);
        $pool->addHttpClient($client2);

        $client1->method('getSendingRequestCount')->willReturn(5);
        $client2->method('getSendingRequestCount')->willReturn(1);
        $client1->method('isDisabled')->willReturn(false);
        $client2->method('isDisabled')->willReturn(false);
        $client2->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $client1->expects($this->never())->method('sendRequest');

        $this->assertSame($response, $pool->sendRequest($request));
    }

    public function testDisablingAllClientsThrows(): void
    {
        $pool = new LeastUsedClientPool();
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $pool->addHttpClient($client);

        $client->expects($this->once())->method('sendRequest')->willThrowException(new HttpException('fail', $request, $response));

        $this->expectException(HttpException::class);
        $pool->sendRequest($request);

        $this->expectException(HttpClientNotFoundException::class);
        $pool->sendRequest($request);
    }

    public function testClientWithZeroRetryDelayGetsReenabled(): void
    {
        $pool = new LeastUsedClientPool();
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $pool->addHttpClient(new HttpClientPoolItem($client, 0));

        $client->expects($this->exactly(2))->method('sendRequest')->with($request)->willThrowException(new HttpException('fail', $request, $response));

        try {
            $pool->sendRequest($request);
            $this->fail('Expected HttpException');
        } catch (HttpException $e) {
        }

        try {
            $pool->sendRequest($request);
            $this->fail('Expected HttpException');
        } catch (HttpException $e) {
        }
    }

    public function testSendAsyncRequestDelegates(): void
    {
        $pool = new LeastUsedClientPool();
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $promise = $this->createMock(Promise::class);

        $pool->addHttpClient($client);
        $client->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn($promise);
        $promise->method('then')->willReturn($promise);

        $this->assertSame($promise, $pool->sendAsyncRequest($request));
    }
}
