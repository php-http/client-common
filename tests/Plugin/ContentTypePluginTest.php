<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class ContentTypePluginTest extends TestCase
{
    public function testAddsJsonContentType(): void
    {
        $request = new Request('POST', 'https://example.com', [], Stream::create(json_encode(['foo' => 'bar'])));
        $handled = $this->handle(new ContentTypePlugin(), $request);

        $this->assertSame('application/json', $handled->getHeaderLine('Content-Type'));
    }

    public function testAddsXmlContentType(): void
    {
        $request = new Request('POST', 'https://example.com', [], Stream::create('<foo>bar</foo>'));
        $handled = $this->handle(new ContentTypePlugin(), $request);

        $this->assertSame('application/xml', $handled->getHeaderLine('Content-Type'));
    }

    public function testDoesNotSetForUnknownContent(): void
    {
        $request = new Request('POST', 'https://example.com', [], Stream::create('foo'));
        $handled = $this->handle(new ContentTypePlugin(), $request);

        $this->assertFalse($handled->hasHeader('Content-Type'));
    }

    public function testDoesNotOverrideExistingHeader(): void
    {
        $request = (new Request('POST', 'https://example.com', ['Content-Type' => 'text/plain'], Stream::create('foo')));
        $handled = $this->handle(new ContentTypePlugin(), $request);

        $this->assertSame('text/plain', $handled->getHeaderLine('Content-Type'));
    }

    public function testDoesNotSetWhenSizeZeroOrUnknown(): void
    {
        $request = new Request('POST', 'https://example.com', [], Stream::create(''));
        $this->assertFalse($this->handle(new ContentTypePlugin(), $request)->hasHeader('Content-Type'));

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);
        $stream->method('getSize')->willReturn(null);
        $stream->method('getContents')->willReturn('');
        $stream->method('rewind');
        $request = (new Request('POST', 'https://example.com'))->withBody($stream);
        $this->assertFalse($this->handle(new ContentTypePlugin(), $request)->hasHeader('Content-Type'));
    }

    public function testSkipDetectionRespectsDefaultLimit(): void
    {
        $plugin = new ContentTypePlugin(['skip_detection' => true]);
        $request = new Request('POST', 'https://example.com', [], Stream::create('<foo>bar</foo>'));
        $handled = $this->handle($plugin, $request);

        $this->assertSame('application/xml', $handled->getHeaderLine('Content-Type'));
    }

    public function testSkipDetectionRespectsCustomLimit(): void
    {
        $plugin = new ContentTypePlugin(['skip_detection' => true, 'size_limit' => 32000000]);
        $request = new Request('POST', 'https://example.com', [], Stream::create('<foo>bar</foo>'));
        $handled = $this->handle($plugin, $request);

        $this->assertSame('application/xml', $handled->getHeaderLine('Content-Type'));
    }

    public function testDoesNotSetWhenSizeLimitReached(): void
    {
        $plugin = new ContentTypePlugin(['skip_detection' => true, 'size_limit' => 8]);
        $request = new Request('POST', 'https://example.com', [], Stream::create('<foo>bar</foo>'));
        $handled = $this->handle($plugin, $request);

        $this->assertFalse($handled->hasHeader('Content-Type'));
    }

    private function handle(ContentTypePlugin $plugin, RequestInterface $request): RequestInterface
    {
        $captured = null;

        $plugin->handleRequest(
            $request,
            function (RequestInterface $request) use (&$captured) {
                $captured = $request;

                return new HttpFulfilledPromise(new Response());
            },
            static function () {
                return new HttpFulfilledPromise(new Response());
            }
        )->wait();

        return $captured ?? $request;
    }
}
