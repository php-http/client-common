<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class PluginClientSpec extends ObjectBehavior
{
    function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\PluginClient');
    }

    function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    function it_is_an_http_async_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    function it_sends_request_with_underlying_client(HttpClient $httpClient, RequestInterface $request, ResponseInterface $response)
    {
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    function it_sends_async_request_with_underlying_client(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->beConstructedWith($httpAsyncClient);
        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }

    function it_sends_async_request_if_no_send_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, ResponseInterface $response, Promise $promise)
    {
        $this->beConstructedWith($httpAsyncClient);
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);
        $promise->wait()->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    function it_prefers_send_request($client, RequestInterface $request, ResponseInterface $response)
    {
        $client->implement('Http\Client\HttpClient');
        $client->implement('Http\Client\HttpAsyncClient');

        $client->sendRequest($request)->willReturn($response);

        $this->beConstructedWith($client);

        $this->sendRequest($request)->shouldReturn($response);
    }

    function it_throws_loop_exception(HttpClient $httpClient, RequestInterface $request, Plugin $plugin)
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

        $this->shouldThrow('Http\Client\Common\Exception\LoopException')->duringSendRequest($request);
    }

    function it_injects_debug_plugins(HttpClient $httpClient, ResponseInterface $response, RequestInterface $request, Plugin $plugin0, Plugin $plugin1, Plugin $debugPlugin)
    {
        $plugin0
            ->handleRequest(
                $request,
                Argument::type('callable'),
                Argument::type('callable')
            )
            ->shouldBeCalledTimes(1)
            ->will(function ($args) {
                return $args[1]($args[0]);
            })
        ;
        $plugin1
            ->handleRequest(
                $request,
                Argument::type('callable'),
                Argument::type('callable')
            )
            ->shouldBeCalledTimes(1)
            ->will(function ($args) {
                return $args[1]($args[0]);
            })
        ;

        $debugPlugin
            ->handleRequest(
                $request,
                Argument::type('callable'),
                Argument::type('callable')
            )
            ->shouldBeCalledTimes(3)
            ->will(function ($args) {
                return $args[1]($args[0]);
            })
        ;

        $httpClient->sendRequest($request)->willReturn($response);

        $this->beConstructedWith($httpClient, [$plugin0, $plugin1], ['debug_plugins'=>[$debugPlugin]]);
        $this->sendRequest($request);
    }
}
