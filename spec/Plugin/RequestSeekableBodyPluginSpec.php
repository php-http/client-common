<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\RequestSeekableBodyPlugin;
use Http\Message\Stream\BufferedStream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class RequestSeekableBodyPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RequestSeekableBodyPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_decorate_request_body_if_not_seekable(RequestInterface $request, StreamInterface $requestStream)
    {
        $request->getBody()->shouldBeCalled()->willReturn($requestStream);
        $requestStream->isSeekable()->shouldBeCalled()->willReturn(false);
        $requestStream->getSize()->willReturn(null);

        $request->withBody(Argument::type(BufferedStream::class))->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, PluginStub::next(), function () {});
    }

    public function it_does_not_decorate_request_body_if_seekable(RequestInterface $request, StreamInterface $requestStream)
    {
        $request->getBody()->shouldBeCalled()->willReturn($requestStream);
        $requestStream->isSeekable()->shouldBeCalled()->willReturn(true);
        $requestStream->getSize()->willReturn(null);

        $request->withBody(Argument::type(BufferedStream::class))->shouldNotBeCalled();

        $this->handleRequest($request, PluginStub::next(), function () {});
    }
}
