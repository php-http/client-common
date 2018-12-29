<?php

namespace spec\Http\Client\Common\Plugin;

use Psr\Http\Message\RequestInterface;
use PhpSpec\ObjectBehavior;

class HeaderAppendPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith([]);
        $this->shouldHaveType('Http\Client\Common\Plugin\HeaderAppendPlugin');
    }

    public function it_is_a_plugin()
    {
        $this->beConstructedWith([]);
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    public function it_appends_the_header(RequestInterface $request)
    {
        $this->beConstructedWith([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $request->withAddedHeader('foo', 'bar')->shouldBeCalled()->willReturn($request);
        $request->withAddedHeader('baz', 'qux')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }
}
