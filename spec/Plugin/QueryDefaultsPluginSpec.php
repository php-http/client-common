<?php

namespace spec\Http\Client\Common\Plugin;

use PhpSpec\Exception\Example\SkippingException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\UriInterface;

class QueryDefaultsPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith([]);
        $this->shouldHaveType('Http\Client\Common\Plugin\QueryDefaultsPlugin');
    }

    public function it_is_a_plugin()
    {
        $this->beConstructedWith([]);
        $this->shouldImplement('Http\Client\Common\Plugin');
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

        $this->handleRequest($request, function () {}, function () {});
    }
}
