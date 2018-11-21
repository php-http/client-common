<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\UriInterface;
use Http\Client\Common\Plugin\QueryDefaultsPlugin;

class QueryDefaultsPluginSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith([]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(QueryDefaultsPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_sets_the_default_header(RequestInterface $request, UriInterface $uri)
    {
        $this->beConstructedWith([
            'foo' => 'bar',
        ]);

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $uri->getQuery()->shouldBeCalled()->willReturn('test=true');
        $uri->withQuery('test=true&foo=bar')->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, PluginStub::next(), function () {});
    }

    public function it_does_not_replace_existing_request_value(RequestInterface $request, UriInterface $uri)
    {
        $this->beConstructedWith([
            'foo' => 'fooDefault',
            'bar' => 'barDefault',
        ]);

        $request->getUri()->shouldBeCalled()->willReturn($uri);
        $uri->getQuery()->shouldBeCalled()->willReturn('foo=new');
        $uri->withQuery('foo=new&bar=barDefault')->shouldBeCalled()->willReturn($uri);
        $request->withUri($uri)->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, PluginStub::next(), function () {});
    }
}
