<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\RequestSeekableBodyPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\Stream\BufferedStream;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class RequestSeekableBodyPluginTest extends TestCase
{
    public function testWrapsNonSeekableBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(false);

        $request->expects($this->exactly(2))->method('getBody')->willReturn($stream);
        $request->expects($this->once())
            ->method('withBody')
            ->with($this->isInstanceOf(BufferedStream::class))
            ->willReturnSelf();

        (new RequestSeekableBodyPlugin())->handleRequest($request, $this->next(), static function () {
        });
    }

    public function testLeavesSeekableBodyUntouched(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);

        $request->expects($this->once())->method('getBody')->willReturn($stream);
        $request->expects($this->never())->method('withBody');

        (new RequestSeekableBodyPlugin())->handleRequest($request, $this->next(), static function () {
        });
    }

    private function next(): callable
    {
        return static function () {
            return new HttpFulfilledPromise(new Response());
        };
    }
}
