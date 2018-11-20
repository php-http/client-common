<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class EmulatedHttpAsyncClientSpec extends ObjectBehavior
{
    public function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\EmulatedHttpAsyncClient');
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    public function it_emulates_a_successful_request(
        HttpClient $httpClient,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf('Http\Client\Promise\HttpFulfilledPromise');
    }

    public function it_emulates_a_failed_request(HttpClient $httpClient, RequestInterface $request)
    {
        $httpClient->sendRequest($request)->willThrow('Http\Client\Exception\TransferException');

        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf('Http\Client\Promise\HttpRejectedPromise');
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
