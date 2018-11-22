<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\EmulatedHttpAsyncClient;
use Http\Client\HttpAsyncClient;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Exception\TransferException;
use Http\Client\Promise\HttpRejectedPromise;

class EmulatedHttpAsyncClientSpec extends ObjectBehavior
{
    public function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmulatedHttpAsyncClient::class);
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
        HttpClient $httpClient,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf(HttpFulfilledPromise::class);
    }

    public function it_emulates_a_failed_request(HttpClient $httpClient, RequestInterface $request)
    {
        $httpClient->sendRequest($request)->willThrow(TransferException::class);

        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
    }

    public function it_decorates_the_underlying_client(
        HttpClient $httpClient,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }
}
