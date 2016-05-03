<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class FlexibleHttpClientSpec extends ObjectBehavior
{
    function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\FlexibleHttpClient');
    }

    function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    function it_throw_exception_if_invalid_client()
    {
        $this->beConstructedWith(null);

        $this->shouldThrow('\LogicException')->duringInstantiation();
    }

    function it_emulates_an_async_client(
        HttpClient $httpClient,
        RequestInterface $syncRequest,
        ResponseInterface $syncResponse,
        RequestInterface $asyncRequest,
        ResponseInterface $asyncResponse
    ) {
        $this->beConstructedWith($httpClient);

        $httpClient->sendRequest($syncRequest)->willReturn($syncResponse);
        $httpClient->sendRequest($asyncRequest)->willReturn($asyncResponse);

        $this->sendRequest($syncRequest)->shouldReturn($syncResponse);
        $promise = $this->sendAsyncRequest($asyncRequest);

        $promise->shouldHaveType('Http\Promise\Promise');
        $promise->wait()->shouldReturn($asyncResponse);
    }

    function it_emulates_a_client(
        HttpAsyncClient $httpAsyncClient,
        RequestInterface $asyncRequest,
        Promise $promise,
        RequestInterface $syncRequest,
        Promise $syncPromise,
        ResponseInterface $syncResponse
    ) {
        $this->beConstructedWith($httpAsyncClient);

        $httpAsyncClient->sendAsyncRequest($asyncRequest)->willReturn($promise);
        $httpAsyncClient->sendAsyncRequest($syncRequest)->willReturn($syncPromise);
        $syncPromise->wait()->willReturn($syncResponse);

        $this->sendAsyncRequest($asyncRequest)->shouldReturn($promise);
        $this->sendRequest($syncRequest)->shouldReturn($syncResponse);
    }

    function it_does_not_emulate_a_client($client, RequestInterface $syncRequest, RequestInterface $asyncRequest)
    {
        $client->implement('Http\Client\HttpClient');
        $client->implement('Http\Client\HttpAsyncClient');

        $client->sendRequest($syncRequest)->shouldBeCalled();
        $client->sendRequest($asyncRequest)->shouldNotBeCalled();
        $client->sendAsyncRequest($asyncRequest)->shouldBeCalled();
        $client->sendAsyncRequest($syncRequest)->shouldNotBeCalled();

        $this->beConstructedWith($client);

        $this->sendRequest($syncRequest);
        $this->sendAsyncRequest($asyncRequest);
    }
}
