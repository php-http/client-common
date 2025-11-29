<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\Exception\HttpClientNoMatchException;
use Http\Client\Common\HttpClientRouter;
use Http\Client\Common\HttpClientRouterInterface;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\RequestMatcher;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientRouterTest extends TestCase
{
    public function testImplementsInterfaces(): void
    {
        $router = new HttpClientRouter();
        $this->assertInstanceOf(HttpClientRouterInterface::class, $router);
        $this->assertInstanceOf(HttpClient::class, $router);
        $this->assertInstanceOf(HttpAsyncClient::class, $router);
    }

    public function testSendRequestDelegatesToMatchingClient(): void
    {
        $router = new HttpClientRouter();
        $matcher = $this->createMock(RequestMatcher::class);
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $router->addClient($client, $matcher);

        $matcher->expects($this->once())->method('matches')->with($request)->willReturn(true);
        $client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);

        $this->assertSame($response, $router->sendRequest($request));
    }

    public function testSendAsyncRequestDelegates(): void
    {
        $router = new HttpClientRouter();
        $matcher = $this->createMock(RequestMatcher::class);
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);
        $promise = $this->createMock(Promise::class);

        $router->addClient($client, $matcher);

        $matcher->expects($this->once())->method('matches')->with($request)->willReturn(true);
        $client->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn($promise);

        $this->assertSame($promise, $router->sendAsyncRequest($request));
    }

    public function testSendRequestThrowsWhenNoClientMatches(): void
    {
        $router = new HttpClientRouter();
        $matcher = $this->createMock(RequestMatcher::class);
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);

        $router->addClient($client, $matcher);
        $matcher->expects($this->once())->method('matches')->willReturn(false);

        $this->expectException(HttpClientNoMatchException::class);
        $router->sendRequest($request);
    }

    public function testSendAsyncRequestThrowsWhenNoClientMatches(): void
    {
        $router = new HttpClientRouter();
        $matcher = $this->createMock(RequestMatcher::class);
        $client = $this->createMock(HttpAsyncClient::class);
        $request = $this->createMock(RequestInterface::class);

        $router->addClient($client, $matcher);
        $matcher->expects($this->once())->method('matches')->willReturn(false);

        $this->expectException(HttpClientNoMatchException::class);
        $router->sendAsyncRequest($request);
    }
}
