<?php

namespace spec\Http\Client\Common\Plugin;

use Psr\Http\Message\RequestInterface;
use PhpSpec\ObjectBehavior;

class HeaderRemovePluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith([]);
        $this->shouldHaveType('Http\Client\Common\Plugin\HeaderRemovePlugin');
    }

    public function it_is_a_plugin()
    {
        $this->beConstructedWith([]);
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    public function it_removes_the_header(RequestInterface $request)
    {
        $this->beConstructedWith([
            'foo',
            'baz',
        ]);

        $request->hasHeader('foo')->shouldBeCalled()->willReturn(false);

        $request->hasHeader('baz')->shouldBeCalled()->willReturn(true);
        $request->withoutHeader('baz')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }
}
