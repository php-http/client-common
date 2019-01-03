<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\Exception\LoopException;
use Http\Client\Common\PluginClient;

class PluginClientSpec extends ObjectBehavior
{
    public function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PluginClient::class);
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement(HttpClient::class);
    }

    public function it_is_an_http_async_client()
    {
        $this->shouldImplement(HttpAsyncClient::class);
    }

    public function it_sends_request_with_underlying_client(HttpClient $httpClient, RequestInterface $request, ResponseInterface $response)
    {
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    public function it_sends_async_request_with_underlying_client(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->beConstructedWith($httpAsyncClient);
        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }

    public function it_sends_async_request_if_no_send_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, ResponseInterface $response, Promise $promise)
    {
        $this->beConstructedWith($httpAsyncClient);
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);
        $promise->wait()->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    public function it_prefers_send_request($client, RequestInterface $request, ResponseInterface $response)
    {
        $client->implement(HttpClient::class);
        $client->implement(HttpAsyncClient::class);

        $client->sendRequest($request)->willReturn($response);

        $this->beConstructedWith($client);

        $this->sendRequest($request)->shouldReturn($response);
    }

    public function it_throws_loop_exception(HttpClient $httpClient, RequestInterface $request, Plugin $plugin)
    {
        $plugin
            ->handleRequest(
                $request,
                Argument::type('callable'),
                Argument::type('callable')
            )
            ->will(function ($args) {
                return $args[2]($args[0]);
            })
        ;

        $this->beConstructedWith($httpClient, [$plugin]);

        $this->shouldThrow(LoopException::class)->duringSendRequest($request);
    }
}
