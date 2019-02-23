<?php

namespace spec\Http\Client\Common\Plugin;

use GuzzleHttp\Psr7\Request;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\ResponseSeekableBodyPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\Stream\BufferedStream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseSeekableBodyPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ResponseSeekableBodyPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_decorate_response_body_if_not_seekable(ResponseInterface $response, StreamInterface $responseStream)
    {
        $next = function () use ($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->getBody()->shouldBeCalled()->willReturn($responseStream);
        $responseStream->isSeekable()->shouldBeCalled()->willReturn(false);
        $responseStream->getSize()->willReturn(null);

        $response->withBody(Argument::type(BufferedStream::class))->shouldBeCalled()->willReturn($response);

        $this->handleRequest(new Request('GET', '/'), $next, function () {});
    }

    public function it_does_not_decorate_response_body_if_seekable(ResponseInterface $response, StreamInterface $responseStream)
    {
        $next = function () use ($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->getBody()->shouldBeCalled()->willReturn($responseStream);
        $responseStream->isSeekable()->shouldBeCalled()->willReturn(true);
        $responseStream->getSize()->willReturn(null);

        $response->withBody(Argument::type(BufferedStream::class))->shouldNotBeCalled();

        $this->handleRequest(new Request('GET', '/'), $next, function () {});
    }
}
