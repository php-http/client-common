<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\HttpClientPool;

use Http\Client\Common\Exception\HttpClientNotFoundException;
use Http\Client\Common\HttpClientPool\HttpClientPoolItem;
use Http\Client\Common\HttpClientPool\RandomClientPool;
use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RandomClientPoolTest extends TestCase
{
    public function testThrowsWhenNoClient(): void
    {
        $pool = new RandomClientPool();
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(HttpClientNotFoundException::class);
        $pool->sendRequest($request);
    }

    public function testSendRequestDelegates(): void
    {
        $pool = new RandomClientPool();
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $pool->addHttpClient($client);

        $client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);

        $this->assertSame($response, $pool->sendRequest($request));
    }

    public function testSendAsyncRequestDelegates(): void
    {
        $pool = new RandomClientPool();
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $promise = $this->createMock(Promise::class);
        $pool->addHttpClient($client);

        $client->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn($promise);
        $promise->method('then')->willReturn($promise);

        $this->assertSame($promise, $pool->sendAsyncRequest($request));
    }

    public function testDisablingClientsThrows(): void
    {
        $pool = new RandomClientPool();
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
        $pool = new RandomClientPool();
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
}
