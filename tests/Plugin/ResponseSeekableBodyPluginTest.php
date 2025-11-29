<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\ResponseSeekableBodyPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\Stream\BufferedStream;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseSeekableBodyPluginTest extends TestCase
{
    public function testWrapsNonSeekableResponseBody(): void
    {
        $plugin = new ResponseSeekableBodyPlugin();
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->expects($this->exactly(2))->method('getBody')->willReturn($stream);
        $stream->method('isSeekable')->willReturn(false);
        $response->expects($this->once())
            ->method('withBody')
            ->with($this->isInstanceOf(BufferedStream::class))
            ->willReturnSelf();

        $plugin->handleRequest(new Request('GET', '/'), function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();
    }

    public function testLeavesSeekableResponseUntouched(): void
    {
        $plugin = new ResponseSeekableBodyPlugin();
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->expects($this->once())->method('getBody')->willReturn($stream);
        $stream->method('isSeekable')->willReturn(true);
        $response->expects($this->never())->method('withBody');

        $result = $plugin->handleRequest(new Request('GET', '/'), function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();

        $this->assertSame($response, $result);
    }
}
