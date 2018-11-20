<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\FlexibleHttpClient;

class FlexibleHttpClientSpec extends ObjectBehavior
{
    public function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FlexibleHttpClient::class);
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement(HttpClient::class);
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement(HttpAsyncClient::class);
    }

    public function it_throw_exception_if_invalid_client()
    {
        $this->beConstructedWith(null);

        $this->shouldThrow(\LogicException::class)->duringInstantiation();
    }

    public function it_emulates_an_async_client(
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

        $promise->shouldHaveType(Promise::class);
        $promise->wait()->shouldReturn($asyncResponse);
    }

    public function it_emulates_a_client(
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

    public function it_does_not_emulate_a_client($client, RequestInterface $syncRequest, RequestInterface $asyncRequest)
    {
        $client->implement(HttpClient::class);
        $client->implement(HttpAsyncClient::class);

        $client->sendRequest($syncRequest)->shouldBeCalled();
        $client->sendRequest($asyncRequest)->shouldNotBeCalled();
        $client->sendAsyncRequest($asyncRequest)->shouldBeCalled();
        $client->sendAsyncRequest($syncRequest)->shouldNotBeCalled();

        $this->beConstructedWith($client);

        $this->sendRequest($syncRequest);
        $this->sendAsyncRequest($asyncRequest);
    }
}
