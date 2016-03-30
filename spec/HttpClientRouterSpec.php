<?php

namespace spec\Http\Client\Common;

use Http\Message\RequestMatcher;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class HttpClientRouterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\HttpClientRouter');
    }

    function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    function it_send_request(RequestMatcher $matcher, HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(true);
        $client->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    function it_send_async_request(RequestMatcher $matcher, HttpAsyncClient $client, RequestInterface $request, Promise $promise)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(true);
        $client->sendAsyncRequest($request)->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }

    function it_throw_exception_on_send_request(RequestMatcher $matcher, HttpClient $client, RequestInterface $request)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(false);

        $this->shouldThrow('Http\Client\Exception\RequestException')->duringSendRequest($request);
    }

    function it_throw_exception_on_send_async_request(RequestMatcher $matcher, HttpAsyncClient $client, RequestInterface $request)
    {
        $this->addClient($client, $matcher);
        $matcher->matches($request)->willReturn(false);

        $this->shouldThrow('Http\Client\Exception\RequestException')->duringSendAsyncRequest($request);
    }
}
