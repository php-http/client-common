<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;

class ContentTypePluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ContentTypePlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_adds_json_content_type_header(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for(json_encode(['foo' => 'bar'])));
        $request->withHeader('Content-Type', 'application/json')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_adds_xml_content_type_header(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for('<foo>bar</foo>'));
        $request->withHeader('Content-Type', 'application/xml')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_does_not_set_content_type_header(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for('foo'));
        $request->withHeader('Content-Type', null)->shouldNotBeCalled();

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_does_not_set_content_type_header_if_already_one(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(true);
        $request->getBody()->shouldNotBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for('foo'));
        $request->withHeader('Content-Type', null)->shouldNotBeCalled();

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_does_not_set_content_type_header_if_size_0_or_unknown(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for());
        $request->withHeader('Content-Type', null)->shouldNotBeCalled();

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_adds_xml_content_type_header_if_size_limit_is_not_reached_using_default_value(RequestInterface $request)
    {
        $this->beConstructedWith([
            'skip_detection' => true,
        ]);

        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for('<foo>bar</foo>'));
        $request->withHeader('Content-Type', 'application/xml')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_adds_xml_content_type_header_if_size_limit_is_not_reached(RequestInterface $request)
    {
        $this->beConstructedWith([
            'skip_detection' => true,
            'size_limit' => 32000000,
        ]);

        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for('<foo>bar</foo>'));
        $request->withHeader('Content-Type', 'application/xml')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }

    public function it_does_not_set_content_type_header_if_size_limit_is_reached(RequestInterface $request)
    {
        $this->beConstructedWith([
            'skip_detection' => true,
            'size_limit' => 8,
        ]);

        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(\GuzzleHttp\Psr7\stream_for('<foo>bar</foo>'));
        $request->withHeader('Content-Type', null)->shouldNotBeCalled();

        $this->handleRequest($request, function () {}, function () {});
    }
}
