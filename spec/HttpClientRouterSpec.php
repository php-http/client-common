<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\Exception\HttpClientNoMatchException;
use Http\Client\Common\HttpClientRouter;
use Http\Message\RequestMatcher;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\HttpClientRouterInterface;

class HttpClientRouterSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(HttpClientRouter::class);
    }

    public function it_is_an_http_client_router()
    {
        $this->shouldImplement(HttpClientRouterInterface::class);
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement(HttpClient::class);
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement(HttpAsyncClient::class);
    }

    public function it_send_request(RequestMatcher $matcher, HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(true);
        $client->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    public function it_send_async_request(RequestMatcher $matcher, HttpAsyncClient $client, RequestInterface $request, Promise $promise)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(true);
        $client->sendAsyncRequest($request)->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }

    public function it_throw_exception_on_send_request(RequestMatcher $matcher, HttpClient $client, RequestInterface $request)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(false);

        $this->shouldThrow(HttpClientNoMatchException::class)->duringSendRequest($request);
    }

    public function it_throw_exception_on_send_async_request(RequestMatcher $matcher, HttpAsyncClient $client, RequestInterface $request)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(false);

        $this->shouldThrow(HttpClientNoMatchException::class)->duringSendAsyncRequest($request);
    }
}
