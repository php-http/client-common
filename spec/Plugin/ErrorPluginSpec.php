<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Promise\HttpFulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin;
use Http\Client\Promise\HttpRejectedPromise;
use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\Exception\ServerErrorException;

class ErrorPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beAnInstanceOf(ErrorPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_throw_client_error_exception_on_4xx_error(RequestInterface $request, ResponseInterface $response)
    {
        $response->getStatusCode()->willReturn(400);
        $response->getReasonPhrase()->willReturn('Bad request');

        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($response->getWrappedObject());
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(ClientErrorException::class)->duringWait();
    }

    public function it_does_not_throw_client_error_exception_on_4xx_error_if_only_server_exception(RequestInterface $request, ResponseInterface $response)
    {
        $this->beConstructedWith(['only_server_exception' => true]);

        $response->getStatusCode()->willReturn(400);
        $response->getReasonPhrase()->willReturn('Bad request');

        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($response->getWrappedObject());
            }
        };

        $this->handleRequest($request, $next, function () {})->shouldReturnAnInstanceOf(HttpFulfilledPromise::class);
    }

    public function it_throw_server_error_exception_on_5xx_error(RequestInterface $request, ResponseInterface $response)
    {
        $response->getStatusCode()->willReturn(500);
        $response->getReasonPhrase()->willReturn('Server error');

        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($response->getWrappedObject());
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(ServerErrorException::class)->duringWait();
    }

    public function it_returns_response(RequestInterface $request, ResponseInterface $response)
    {
        $response->getStatusCode()->willReturn(200);

        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($response->getWrappedObject());
            }
        };

        $this->handleRequest($request, $next, function () {})->shouldReturnAnInstanceOf(HttpFulfilledPromise::class);
    }
}
