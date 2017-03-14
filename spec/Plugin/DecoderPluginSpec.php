<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Promise\HttpFulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use PhpSpec\Exception\Example\SkippingException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DecoderPluginSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\DecoderPlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_decodes(RequestInterface $request, ResponseInterface $response, StreamInterface $stream)
    {
        if(defined('HHVM_VERSION')) {
            throw new SkippingException('Skipping test on hhvm, as there is no chunk encoding on hhvm');
        }

        $request->withHeader('TE', ['gzip', 'deflate', 'chunked'])->shouldBeCalled()->willReturn($request);
        $request->withHeader('Accept-Encoding', ['gzip', 'deflate'])->shouldBeCalled()->willReturn($request);
        $next = function () use($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->hasHeader('Transfer-Encoding')->willReturn(true);
        $response->getHeader('Transfer-Encoding')->willReturn(['chunked']);
        $response->getBody()->willReturn($stream);
        $response->withBody(Argument::type('Http\Message\Encoding\DechunkStream'))->willReturn($response);
        $response->withHeader('Transfer-Encoding', [])->willReturn($response);
        $response->hasHeader('Content-Encoding')->willReturn(false);

        $stream->isReadable()->willReturn(true);
        $stream->isWritable()->willReturn(false);
        $stream->eof()->willReturn(false);

        $this->handleRequest($request, $next, function () {});
    }

    function it_decodes_gzip(RequestInterface $request, ResponseInterface $response, StreamInterface $stream)
    {
        $request->withHeader('TE', ['gzip', 'deflate', 'chunked'])->shouldBeCalled()->willReturn($request);
        $request->withHeader('Accept-Encoding', ['gzip', 'deflate'])->shouldBeCalled()->willReturn($request);
        $next = function () use($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->hasHeader('Transfer-Encoding')->willReturn(false);
        $response->hasHeader('Content-Encoding')->willReturn(true);
        $response->getHeader('Content-Encoding')->willReturn(['gzip']);
        $response->getBody()->willReturn($stream);
        $response->withBody(Argument::type('Http\Message\Encoding\GzipDecodeStream'))->willReturn($response);
        $response->withHeader('Content-Encoding', [])->willReturn($response);

        $stream->isReadable()->willReturn(true);
        $stream->isWritable()->willReturn(false);
        $stream->eof()->willReturn(false);

        $this->handleRequest($request, $next, function () {});
    }

    function it_decodes_deflate(RequestInterface $request, ResponseInterface $response, StreamInterface $stream)
    {
        $request->withHeader('TE', ['gzip', 'deflate', 'chunked'])->shouldBeCalled()->willReturn($request);
        $request->withHeader('Accept-Encoding', ['gzip', 'deflate'])->shouldBeCalled()->willReturn($request);
        $next = function () use($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->hasHeader('Transfer-Encoding')->willReturn(false);
        $response->hasHeader('Content-Encoding')->willReturn(true);
        $response->getHeader('Content-Encoding')->willReturn(['deflate']);
        $response->getBody()->willReturn($stream);
        $response->withBody(Argument::type('Http\Message\Encoding\DecompressStream'))->willReturn($response);
        $response->withHeader('Content-Encoding', [])->willReturn($response);

        $stream->isReadable()->willReturn(true);
        $stream->isWritable()->willReturn(false);
        $stream->eof()->willReturn(false);

        $this->handleRequest($request, $next, function () {});
    }

    function it_does_not_decode_with_content_encoding(RequestInterface $request, ResponseInterface $response)
    {
        $this->beConstructedWith(['use_content_encoding' => false]);

        $request->withHeader('TE', ['gzip', 'deflate', 'chunked'])->shouldBeCalled()->willReturn($request);
        $request->withHeader('Accept-Encoding', ['gzip', 'deflate'])->shouldNotBeCalled();
        $next = function () use($response) {
            return new HttpFulfilledPromise($response->getWrappedObject());
        };

        $response->hasHeader('Transfer-Encoding')->willReturn(false);
        $response->hasHeader('Content-Encoding')->shouldNotBeCalled();

        $this->handleRequest($request, $next, function () {});
    }
}
