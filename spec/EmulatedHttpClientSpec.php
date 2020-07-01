<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\EmulatedHttpClient;
use Http\Client\Exception;
use Http\Client\Exception\TransferException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class EmulatedHttpClientSpec extends ObjectBehavior
{
    public function let(HttpAsyncClient $httpAsyncClient)
    {
        $this->beConstructedWith($httpAsyncClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmulatedHttpClient::class);
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement(HttpClient::class);
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement(HttpAsyncClient::class);
    }

    public function it_emulates_a_successful_request(
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

    public function it_emulates_a_failed_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $promise->wait()->shouldBeCalled();
        $promise->getState()->willReturn(Promise::REJECTED);
        $promise->wait()->willThrow(new TransferException());

        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->shouldThrow(Exception::class)->duringSendRequest($request);
    }

    public function it_decorates_the_underlying_client(
        HttpAsyncClient $httpAsyncClient,
        RequestInterface $request,
        Promise $promise
    ) {
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }
}
