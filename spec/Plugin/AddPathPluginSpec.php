<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\Plugin;

class AddPathPluginSpec extends ObjectBehavior
{
    function let(UriInterface $uri)
    {
        $this->beConstructedWith($uri);
    }

    function it_is_initializable(UriInterface $uri)
    {
        $uri->getPath()->shouldBeCalled()->willReturn('/api');

        $this->shouldHaveType(AddPathPlugin::class);
    }

    function it_is_a_plugin(UriInterface $uri)
    {
        $uri->getPath()->shouldBeCalled()->willReturn('/api');

        $this->shouldImplement(Plugin::class);
    }

    function it_adds_path(
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

    function it_throws_exception_on_trailing_slash(UriInterface $host)
    {
        $host->getPath()->shouldBeCalled()->willReturn('/api/');

        $this->beConstructedWith($host);
        $this->shouldThrow(\LogicException::class)->duringInstantiation();
    }

    function it_throws_exception_on_empty_path(UriInterface $host)
    {
        $host->getPath()->shouldBeCalled()->willReturn('');

        $this->beConstructedWith($host);
        $this->shouldThrow(\LogicException::class)->duringInstantiation();
    }
}
