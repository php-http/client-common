<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\Encoding\ChunkStream;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class ContentLengthPluginTest extends TestCase
{
    public function testSetsContentLengthWhenSizeKnown(): void
    {
        $plugin = new ContentLengthPlugin();
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->expects($this->once())->method('hasHeader')->with('Content-Length')->willReturn(false);
        $request->expects($this->once())->method('getBody')->willReturn($stream);
        $stream->expects($this->exactly(2))->method('getSize')->willReturn(100);
        $request->expects($this->once())->method('withHeader')->with('Content-Length', '100')->willReturnSelf();

        $plugin->handleRequest($request, $this->next(), static function () {});
    }

    public function testStreamsChunkedWhenSizeUnknown(): void
    {
        $plugin = new ContentLengthPlugin();
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->expects($this->once())->method('hasHeader')->with('Content-Length')->willReturn(false);
        $request->expects($this->once())->method('getBody')->willReturn($stream);
        $stream->expects($this->once())->method('getSize')->willReturn(null);
        $request->expects($this->once())->method('withBody')->with($this->isInstanceOf(ChunkStream::class))->willReturnSelf();
        $request->expects($this->once())->method('withAddedHeader')->with('Transfer-Encoding', 'chunked')->willReturnSelf();

        $plugin->handleRequest($request, $this->next(), static function () {});
    }

    private function next(): callable
    {
        return static function () {
            return new HttpFulfilledPromise(new Response());
        };
    }
}
