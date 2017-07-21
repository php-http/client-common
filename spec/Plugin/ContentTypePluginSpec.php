<?php

namespace spec\Http\Client\Common\Plugin;

use PhpSpec\Exception\Example\SkippingException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContentTypePluginSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\ContentTypePlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_adds_json_content_type_header(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn(json_encode(['foo' => 'bar']));
        $request->withHeader('Content-Type', 'application/json')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }

    function it_adds_xml_content_type_header(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn('<foo>bar</foo>');
        $request->withHeader('Content-Type', 'application/xml')->shouldBeCalled()->willReturn($request);

        $this->handleRequest($request, function () {}, function () {});
    }

    function it_does_not_set_content_type_header(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(false);
        $request->getBody()->shouldBeCalled()->willReturn('foo');
        $request->withHeader('Content-Type', null)->shouldNotBeCalled();

        $this->handleRequest($request, function () {}, function () {});
    }

    function it_does_not_set_content_type_header_if_already_one(RequestInterface $request)
    {
        $request->hasHeader('Content-Type')->shouldBeCalled()->willReturn(true);
        $request->getBody()->shouldNotBeCalled()->willReturn('foo');
        $request->withHeader('Content-Type', null)->shouldNotBeCalled();

        $this->handleRequest($request, function () {}, function () {});
    }

}
