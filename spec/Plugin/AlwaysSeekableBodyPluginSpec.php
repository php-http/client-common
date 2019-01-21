<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AlwaysSeekableBodyPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\Stream\BufferedStream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AlwaysSeekableBodyPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AlwaysSeekableBodyPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_decorate_response_body_if_not_seekable(RequestInterface $request, ResponseInterface $response, StreamInterface $responseStream, StreamInterface $requestStream)
    {
        $next = function () use ($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $request->getBody()->shouldBeCalled()->willReturn($requestStream);
        $requestStream->isSeekable()->shouldBeCalled()->willReturn(true);

        $response->getBody()->shouldBeCalled()->willReturn($responseStream);
        $responseStream->isSeekable()->shouldBeCalled()->willReturn(false);
        $responseStream->getSize()->willReturn(null);

        $response->withBody(Argument::type(BufferedStream::class))->shouldBeCalled()->willReturn($response);

        $this->handleRequest($request, $next, function () {});
    }

    public function it_does_not_decorate_response_body_if_seekable(RequestInterface $request, ResponseInterface $response, StreamInterface $responseStream, StreamInterface $requestStream)
    {
        $next = function () use ($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $request->getBody()->shouldBeCalled()->willReturn($requestStream);
        $requestStream->isSeekable()->shouldBeCalled()->willReturn(true);

        $response->getBody()->shouldBeCalled()->willReturn($responseStream);
        $responseStream->isSeekable()->shouldBeCalled()->willReturn(true);
        $responseStream->getSize()->willReturn(null);

        $response->withBody(Argument::type(BufferedStream::class))->shouldNotBeCalled();

        $this->handleRequest($request, $next, function () {});
    }

    public function it_decorate_request_body_if_not_seekable(RequestInterface $request, ResponseInterface $response, StreamInterface $responseStream, StreamInterface $requestStream)
    {
        $next = function () use ($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->getBody()->willReturn($responseStream);
        $responseStream->isSeekable()->willReturn(true);

        $request->getBody()->shouldBeCalled()->willReturn($requestStream);
        $requestStream->isSeekable()->shouldBeCalled()->willReturn(false);
        $requestStream->getSize()->willReturn(null);

        $request->withBody(Argument::type(BufferedStream::class))->shouldBeCalled();

        $this->handleRequest($request, $next, function () {});
    }

    public function it_does_not_decorate_request_body_if_seekable(RequestInterface $request, ResponseInterface $response, StreamInterface $responseStream, StreamInterface $requestStream)
    {
        $next = function () use ($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->getBody()->willReturn($responseStream);
        $responseStream->isSeekable()->willReturn(true);

        $request->getBody()->shouldBeCalled()->willReturn($requestStream);
        $requestStream->isSeekable()->shouldBeCalled()->willReturn(true);
        $requestStream->getSize()->willReturn(null);

        $request->withBody(Argument::type(BufferedStream::class))->shouldNotBeCalled();

        $this->handleRequest($request, $next, function () {});
    }
}
