<?php

namespace spec\Http\Client\Common\Plugin;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\Plugin;

class BaseUriPluginSpec extends ObjectBehavior
{
    public function let(UriInterface $uri)
    {
        $this->beConstructedWith($uri);
    }

    public function it_is_initializable(UriInterface $uri)
    {
        $uri->getHost()->shouldBeCalled()->willReturn('example.com');
        $uri->getPath()->shouldBeCalled()->willReturn('/api');

        $this->shouldHaveType(BaseUriPlugin::class);
    }

    public function it_is_a_plugin(UriInterface $uri)
    {
        $uri->getHost()->shouldBeCalled()->willReturn('example.com');
        $uri->getPath()->shouldBeCalled()->willReturn('/api');

        $this->shouldImplement(Plugin::class);
    }

    public function it_adds_domain_and_path(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $host->getScheme()->shouldBeCalled()->willReturn('http://');
        $host->getHost()->shouldBeCalled()->willReturn('example.com');
        $host->getPort()->shouldBeCalled()->willReturn(8000);
        $host->getPath()->shouldBeCalled()->willReturn('/api');

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withScheme('http://')->shouldBeCalled()->willReturn($uri);
        $uri->withHost('example.com')->shouldBeCalled()->willReturn($uri);
        $uri->withPort(8000)->shouldBeCalled()->willReturn($uri);
        $uri->withPath('/api/users')->shouldBeCalled()->willReturn($uri);
        $uri->getHost()->shouldBeCalled()->willReturn('');
        $uri->getPath()->shouldBeCalled()->willReturn('/users');

        $this->beConstructedWith($host);
        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_adds_domain(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $host->getScheme()->shouldBeCalled()->willReturn('http://');
        $host->getHost()->shouldBeCalled()->willReturn('example.com');
        $host->getPort()->shouldBeCalled()->willReturn(8000);
        $host->getPath()->shouldBeCalled()->willReturn('/');

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withScheme('http://')->shouldBeCalled()->willReturn($uri);
        $uri->withHost('example.com')->shouldBeCalled()->willReturn($uri);
        $uri->withPort(8000)->shouldBeCalled()->willReturn($uri);
        $uri->getHost()->shouldBeCalled()->willReturn('');

        $this->beConstructedWith($host);
        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_replaces_domain_and_adds_path(
        RequestInterface $request,
        UriInterface $host,
        UriInterface $uri
    ) {
        $host->getScheme()->shouldBeCalled()->willReturn('http://');
        $host->getHost()->shouldBeCalled()->willReturn('example.com');
        $host->getPort()->shouldBeCalled()->willReturn(8000);
        $host->getPath()->shouldBeCalled()->willReturn('/api');

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $uri->withScheme('http://')->shouldBeCalled()->willReturn($uri);
        $uri->withHost('example.com')->shouldBeCalled()->willReturn($uri);
        $uri->withPort(8000)->shouldBeCalled()->willReturn($uri);
        $uri->withPath('/api/users')->shouldBeCalled()->willReturn($uri);
        $uri->getPath()->shouldBeCalled()->willReturn('/users');

        $this->beConstructedWith($host, ['replace' => true]);
        $this->handleRequest($request, function () {}, function () {});
    }
}
