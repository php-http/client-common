<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\HeaderAppendPlugin;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;

class HeaderAppendPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith([]);
        $this->shouldHaveType(HeaderAppendPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->beConstructedWith([]);
        $this->shouldImplement(Plugin::class);
    }

    public function it_appends_the_header(RequestInterface $request)
    {
        $this->beConstructedWith([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $request->withAddedHeader('foo', 'bar')->shouldBeCalled()->willReturn($request);
        $request->withAddedHeader('baz', 'qux')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, PluginStub::next(), function () {});
    }
}
