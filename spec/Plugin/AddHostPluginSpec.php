<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AddHostPlugin;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class AddHostPluginSpec extends ObjectBehavior
{
    public function let(UriInterface $uri)
    {
        $this->beConstructedWith($uri);
    }

    public function it_is_initializable(UriInterface $uri)
    {
        $uri->getHost()->shouldBeCalled()->willReturn('example.com');

        $this->shouldHaveType(AddHostPlugin::class);
    }

    public function it_is_a_plugin(UriInterface $uri)
    {
        $uri->getHost()->shouldBeCalled()->willReturn('example.com');

        $this->shouldImplement(Plugin::class);
    }

    public function it_adds_domain(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $host->getScheme()->shouldBeCalled()->willReturn('http://');
        $host->getHost()->shouldBeCalled()->willReturn('example.com');
        $host->getPort()->shouldBeCalled()->willReturn(8000);

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withScheme('http://')->shouldBeCalled()->willReturn($uri);
        $uri->withHost('example.com')->shouldBeCalled()->willReturn($uri);
        $uri->withPort(8000)->shouldBeCalled()->willReturn($uri);
        $uri->getHost()->shouldBeCalled()->willReturn('');

        $this->beConstructedWith($host);
        $this->handleRequest($request, PluginStub::next(), function () {});
    }

    public function it_replaces_domain(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $host->getScheme()->shouldBeCalled()->willReturn('http://');
        $host->getHost()->shouldBeCalled()->willReturn('example.com');
        $host->getPort()->shouldBeCalled()->willReturn(8000);

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withScheme('http://')->shouldBeCalled()->willReturn($uri);
        $uri->withHost('example.com')->shouldBeCalled()->willReturn($uri);
        $uri->withPort(8000)->shouldBeCalled()->willReturn($uri);

        $this->beConstructedWith($host, ['replace' => true]);
        $this->handleRequest($request, PluginStub::next(), function () {});
    }

    public function it_does_nothing_when_domain_exists(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $uri->getHost()->shouldBeCalled()->willReturn('default.com');

        $this->beConstructedWith($host);
        $this->handleRequest($request, PluginStub::next(), function () {});
    }
}
