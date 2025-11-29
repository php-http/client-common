<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\Encoding\DechunkStream;
use Http\Message\Encoding\DecompressStream;
use Http\Message\Encoding\GzipDecodeStream;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DecoderPluginTest extends TestCase
{
    public function testDecodesChunkedResponses(): void
    {
        $plugin = new DecoderPlugin();
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->expects($this->exactly(2))
            ->method('withHeader')
            ->withConsecutive(
                [$this->equalTo('Accept-Encoding'), $this->isType('array')],
                [$this->equalTo('TE'), $this->callback(function (array $encodings) {
                    $this->assertContains('chunked', $encodings);

                    return true;
                })]
            )
            ->willReturnSelf();

        $response->expects($this->exactly(2))
            ->method('hasHeader')
            ->withConsecutive(['Transfer-Encoding'], ['Content-Encoding'])
            ->willReturnOnConsecutiveCalls(true, false);
        $response->expects($this->once())->method('getHeader')->with('Transfer-Encoding')->willReturn(['chunked']);
        $response->expects($this->once())->method('getBody')->willReturn($stream);
        $response->expects($this->once())->method('withBody')->with($this->isInstanceOf(DechunkStream::class))->willReturnSelf();
        $response->expects($this->once())->method('withoutHeader')->with('Transfer-Encoding')->willReturnSelf();

        $plugin->handleRequest(
            $request,
            function () use ($response) {
                return new HttpFulfilledPromise($response);
            },
            static function () {
            }
        )->wait();
    }

    public function testDecodesGzipResponses(): void
    {
        $plugin = new DecoderPlugin();
        $request = $this->mockRequestWithHeaders();
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->expects($this->exactly(2))
            ->method('hasHeader')
            ->withConsecutive(['Transfer-Encoding'], ['Content-Encoding'])
            ->willReturnOnConsecutiveCalls(false, true);
        $response->expects($this->once())->method('getHeader')->with('Content-Encoding')->willReturn(['gzip']);
        $response->expects($this->once())->method('getBody')->willReturn($stream);
        $response->expects($this->once())->method('withBody')->with($this->isInstanceOf(GzipDecodeStream::class))->willReturnSelf();
        $response->expects($this->once())->method('withoutHeader')->with('Content-Encoding')->willReturnSelf();

        $plugin->handleRequest(
            $request,
            function () use ($response) {
                return new HttpFulfilledPromise($response);
            },
            static function () {
            }
        )->wait();
    }

    public function testDecodesDeflateResponses(): void
    {
        $plugin = new DecoderPlugin();
        $request = $this->mockRequestWithHeaders();
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->expects($this->exactly(2))
            ->method('hasHeader')
            ->withConsecutive(['Transfer-Encoding'], ['Content-Encoding'])
            ->willReturnOnConsecutiveCalls(false, true);
        $response->expects($this->once())->method('getHeader')->with('Content-Encoding')->willReturn(['deflate']);
        $response->expects($this->once())->method('getBody')->willReturn($stream);
        $response->expects($this->once())->method('withBody')->with($this->isInstanceOf(DecompressStream::class))->willReturnSelf();
        $response->expects($this->once())->method('withoutHeader')->with('Content-Encoding')->willReturnSelf();

        $plugin->handleRequest(
            $request,
            function () use ($response) {
                return new HttpFulfilledPromise($response);
            },
            static function () {
            }
        )->wait();
    }

    public function testSkipsContentEncodingWhenDisabled(): void
    {
        $plugin = new DecoderPlugin(['use_content_encoding' => false]);
        $request = $this->createMock(RequestInterface::class);
        $response = new Response();

        $request->expects($this->once())
            ->method('withHeader')
            ->with($this->equalTo('TE'), $this->callback(function (array $encodings) {
                $this->assertContains('chunked', $encodings);

                return true;
            }))
            ->willReturnSelf();

        $plugin->handleRequest(
            $request,
            function () use ($response) {
                return new HttpFulfilledPromise($response);
            },
            static function () {
            }
        )->wait();
    }

    private function mockRequestWithHeaders(): RequestInterface
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->exactly(2))
            ->method('withHeader')
            ->withConsecutive(
                [$this->equalTo('Accept-Encoding'), $this->isType('array')],
                [$this->equalTo('TE'), $this->isType('array')]
            )
            ->willReturnSelf();

        return $request;
    }
}
