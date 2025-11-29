<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\HttpClientPool;

use Http\Client\Common\HttpClientPool\HttpClientPoolItem;
use Http\Client\Exception\RequestException;
use Http\Client\Exception\TransferException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Client\Promise\HttpRejectedPromise;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientPoolItemTest extends TestCase
{
    public function testImplementsClients(): void
    {
        $client = $this->createMock(HttpClient::class);
        $item = new HttpClientPoolItem($client);
        $this->assertInstanceOf(HttpClient::class, $item);
        $this->assertInstanceOf(HttpAsyncClient::class, $item);
    }

    public function testSendRequestSuccess(): void
    {
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);

        $item = new HttpClientPoolItem($client);
        $this->assertSame($response, $item->sendRequest($request));
    }

    public function testSendRequestDisablesOnFailure(): void
    {
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $exception = new TransferException();
        $client->expects($this->once())->method('sendRequest')->willThrowException($exception);

        $item = new HttpClientPoolItem($client);
        try {
            $item->sendRequest($request);
            $this->fail('Expected exception');
        } catch (TransferException $e) {
            $this->assertTrue($item->isDisabled());
        }

        $this->expectException(RequestException::class);
        $item->sendRequest($request);
    }

    public function testSendRequestReenablesWhenRetryDelayZero(): void
    {
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $exception = new TransferException();
        $client->expects($this->exactly(2))->method('sendRequest')->with($request)->willThrowException($exception);

        $item = new HttpClientPoolItem($client, 0);

        try {
            $item->sendRequest($request);
        } catch (TransferException $e) {
            $this->assertFalse($item->isDisabled());
        }

        $this->expectException(TransferException::class);
        $item->sendRequest($request);
    }

    public function testSendAsyncRequestDisablesOnFailure(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $promise = new HttpRejectedPromise(new TransferException());
        $client->expects($this->once())->method('sendAsyncRequest')->willReturn($promise);

        $item = new HttpClientPoolItem($client);
        $this->assertInstanceOf(HttpRejectedPromise::class, $item->sendAsyncRequest($request));
        $this->assertTrue($item->isDisabled());

        $this->expectException(RequestException::class);
        $item->sendAsyncRequest($request);
    }

    public function testSendAsyncRequestReenablesWhenRetryDelayZero(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $promise = new HttpRejectedPromise(new TransferException());
        $client->expects($this->exactly(2))->method('sendAsyncRequest')->willReturn($promise);

        $item = new HttpClientPoolItem($client, 0);
        $item->sendAsyncRequest($request);
        $this->assertFalse($item->isDisabled());
        $this->assertInstanceOf(HttpRejectedPromise::class, $item->sendAsyncRequest($request));
    }

    public function testRequestCountTracksPendingPromises(): void
    {
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $promise = new NotResolvingPromise($response);

        $client->expects($this->exactly(2))->method('sendAsyncRequest')->with($request)->willReturn($promise);

        $item = new HttpClientPoolItem($client, 0);

        $this->assertSame(0, $item->getSendingRequestCount());
        $item->sendAsyncRequest($request);
        $this->assertSame(1, $item->getSendingRequestCount());
        $item->sendAsyncRequest($request);
        $this->assertSame(2, $item->getSendingRequestCount());
        $promise->wait(false);
        $this->assertSame(0, $item->getSendingRequestCount());
    }
}

class NotResolvingPromise implements Promise
{
    private $queue = [];

    private $state = Promise::PENDING;

    private $response;

    private $exception;

    public function __construct(?ResponseInterface $response = null, ?\Http\Client\Exception $exception = null)
    {
        $this->response = $response;
        $this->exception = $exception;
    }

    public function then(?callable $onFulfilled = null, ?callable $onRejected = null)
    {
        $this->queue[] = [$onFulfilled, $onRejected];

        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function wait($unwrap = true)
    {
        while (count($this->queue) > 0) {
            [$onFulfilled, $onRejected] = array_shift($this->queue);

            if (null !== $this->response && null !== $onFulfilled) {
                $this->response = $onFulfilled($this->response);
                $this->exception = null;
            } elseif (null !== $this->exception && null !== $onRejected) {
                $this->response = null;
                $this->exception = $onRejected($this->exception);
            }
        }

        if (null !== $this->response) {
            $this->state = Promise::FULFILLED;

            return $this->response;
        }

        if (null !== $this->exception) {
            $this->state = Promise::REJECTED;

            throw $this->exception;
        }
    }
}
