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

    /**
     * @testWith ["https://example.com/path?query=value", "https://example.com?query=value", "https://example.com?query=value"]
     *           ["https://example.com/path?query=value", "https://example.com/?query=value", "https://example.com/?query=value"]
     *           ["https://example.com", "https://example.com?query=value", "https://example.com?query=value"]
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
