<?php

namespace spec\Http\Client\Common\Plugin;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use PhpSpec\ObjectBehavior;

class AddPathPluginSpec extends ObjectBehavior
{
    public function let(UriInterface $uri)
    {
        $this->beConstructedWith($uri);
    }

    public function it_is_initializable(UriInterface $uri)
    {
        $uri->getPath()->shouldBeCalled()->willReturn('/api');

        $this->shouldHaveType('Http\Client\Common\Plugin\AddPathPlugin');
    }

    public function it_is_a_plugin(UriInterface $uri)
    {
        $uri->getPath()->shouldBeCalled()->willReturn('/api');

        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    public function it_adds_path(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $host->getPath()->shouldBeCalled()->willReturn('/api');

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withPath('/api/users')->shouldBeCalled()->willReturn($uri);
        $uri->getPath()->shouldBeCalled()->willReturn('/users');

        $this->beConstructedWith($host);
        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_removes_ending_slashes(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $host2,
        UriInterface $uri
    ) {
        $host->getPath()->shouldBeCalled()->willReturn('/api/');
        $host2->getPath()->shouldBeCalled()->willReturn('/api');
        $host->withPath('/api')->shouldBeCalled()->willReturn($host2);

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withPath('/api/users')->shouldBeCalled()->willReturn($uri);
        $uri->getPath()->shouldBeCalled()->willReturn('/users');

        $this->beConstructedWith($host);
        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_throws_exception_on_empty_path(UriInterface $host)
    {
        $host->getPath()->shouldBeCalled()->willReturn('');

        $this->beConstructedWith($host);
        $this->shouldThrow('\LogicException')->duringInstantiation();
    }
}
