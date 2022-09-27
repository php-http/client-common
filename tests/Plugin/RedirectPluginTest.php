<?php
declare(strict_types=1);

namespace Plugin;

use Http\Client\Common\Exception\CircularRedirectionException;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Promise\FulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RedirectPluginTest extends TestCase
{
    public function testCircularDetection(): void
    {
        $this->expectException(CircularRedirectionException::class);
        (new RedirectPlugin())->handleRequest(
            new Request('GET', 'https://example.com/path?query=value'),
            function () {
                return new FulfilledPromise(new Response(302, ['Location' => 'https://example.com/path?query=value']));
            },
            function () {}
        )->wait();
    }

    public function provideRedirections(): array
    {
        return [
            'no path on target' => ["https://example.com/path?query=value", "https://example.com?query=value", "https://example.com?query=value"],
            'root path on target' => ["https://example.com/path?query=value", "https://example.com/?query=value", "https://example.com/?query=value"],
            'redirect to query' => ["https://example.com", "https://example.com?query=value", "https://example.com?query=value"],
            'redirect to different domain without port' => ["https://example.com:8000", "https://foo.com?query=value", "https://foo.com?query=value"],
            'network-path redirect, preserve scheme' => ["https://example.com:8000", "//foo.com/path?query=value", "https://foo.com/path?query=value"],
            'absolute-path redirect, preserve host' => ["https://example.com:8000", "/path?query=value", "https://example.com:8000/path?query=value"],
            'relative-path redirect, append' => ["https://example.com:8000/path/", "sub/path?query=value", "https://example.com:8000/path/sub/path?query=value"],
            'relative-path on non-folder' => ["https://example.com:8000/path/foo", "sub/path?query=value", "https://example.com:8000/path/sub/path?query=value"],
            'relative-path moving up' => ["https://example.com:8000/path/", "../other?query=value", "https://example.com:8000/other?query=value"],
            'relative-path with ./' => ["https://example.com:8000/path/", "./other?query=value", "https://example.com:8000/path/other?query=value"],
            'relative-path with //' => ["https://example.com:8000/path/", "other//sub?query=value", "https://example.com:8000/path/other//sub?query=value"],
            'relative-path redirect with only query' => ["https://example.com:8000/path", "?query=value", "https://example.com:8000/path?query=value"],
       ];
    }

    /**
     * @dataProvider provideRedirections
     */
    public function testTargetUriMappingFromLocationHeader(string $originalUri, string $locationUri, string $targetUri): void
    {
        $response = (new RedirectPlugin())->handleRequest(
            new Request('GET', $originalUri),
            function () use ($locationUri) {
                return new FulfilledPromise(new Response(302, ['Location' => $locationUri]));
            },
            function (RequestInterface $request) {
                return new FulfilledPromise(new Response(200, ['uri' => $request->getUri()->__toString()]));
            }
        )->wait();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($targetUri, $response->getHeaderLine('uri'));
    }
}
