<?php

namespace spec\Http\Client\Common\Plugin;

use PhpSpec\Exception\Example\SkippingException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin;

class ContentLengthPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ContentLengthPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_adds_content_length_header(RequestInterface $request, StreamInterface $stream)
    {
        $request->hasHeader('Content-Length')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn($stream);
        $stream->getSize()->shouldBeCalled()->willReturn(100);
        $request->withHeader('Content-Length', '100')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, PluginStub::next(), function () {});
    }

    public function it_streams_chunked_if_no_size(RequestInterface $request, StreamInterface $stream)
    {
        if (defined('HHVM_VERSION')) {
            throw new SkippingException('Skipping test on hhvm, as there is no chunk encoding on hhvm');
        }

        $request->hasHeader('Content-Length')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn($stream);

        $stream->getSize()->shouldBeCalled()->willReturn(null);
        $request->withBody(Argument::type('Http\Message\Encoding\ChunkStream'))->shouldBeCalled()->willReturn($request);
        $request->withAddedHeader('Transfer-Encoding', 'chunked')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, PluginStub::next(), function () {});
    }
}
