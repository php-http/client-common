<?php

namespace spec\Http\Client\Common;

use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class EmulatedHttpClientSpec extends ObjectBehavior
{
    function let(HttpAsyncClient $httpAsyncClient)
    {
        $this->beConstructedWith($httpAsyncClient);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\EmulatedHttpClient');
    }

    function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    function it_emulates_a_successful_request(
        HttpAsyncClient $httpAsyncClient,
        RequestInterface $request,
        Promise $promise,
        ResponseInterface $response
    ) {
        $promise->wait()->shouldBeCalled();
        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->willReturn($response);

        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->sendRequest($request)->shouldReturn($response);
    }

    function it_emulates_a_failed_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $promise->wait()->shouldBeCalled();
        $promise->getState()->willReturn(Promise::REJECTED);
        $promise->wait()->willThrow(new TransferException());

        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->shouldThrow('Http\Client\Exception')->duringSendRequest($request);
    }

    function it_decorates_the_underlying_client(
        HttpAsyncClient $httpAsyncClient,
        RequestInterface $request,
        Promise $promise
    ) {
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }
}
