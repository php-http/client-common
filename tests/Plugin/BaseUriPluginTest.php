<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class BaseUriPluginTest extends TestCase
{
    public function testAddsHostAndPathWhenMissing(): void
    {
        $plugin = new BaseUriPlugin(new Uri('http://example.com:8000/api'));
        $request = new Request('GET', '/users');

        $this->handle($plugin, $request, function (RequestInterface $request): void {
            $uri = $request->getUri();
            $this->assertSame('example.com', $uri->getHost());
            $this->assertSame('http', $uri->getScheme());
            $this->assertSame(8000, $uri->getPort());
            $this->assertSame('/api/users', $uri->getPath());
        });
    }

    public function testAddsOnlyHostWhenBaseUriHasNoPath(): void
    {
        $plugin = new BaseUriPlugin(new Uri('http://example.com:8000/'));
        $request = new Request('GET', '/foo');

        $this->handle($plugin, $request, function (RequestInterface $request): void {
            $uri = $request->getUri();
            $this->assertSame('example.com', $uri->getHost());
            $this->assertSame('/foo', $uri->getPath());
        });
    }

    public function testReplacesHostAndAddsPathWhenConfigured(): void
    {
        $plugin = new BaseUriPlugin(new Uri('http://example.com:8000/api'), ['replace' => true]);
        $request = new Request('GET', 'https://existing.com/users');

        $this->handle($plugin, $request, function (RequestInterface $request): void {
            $uri = $request->getUri();
            $this->assertSame('example.com', $uri->getHost());
            $this->assertSame('/api/users', $uri->getPath());
        });
    }

    private function handle(BaseUriPlugin $plugin, RequestInterface $request, callable $assert): void
    {
        $plugin->handleRequest(
            $request,
            function (RequestInterface $request) use ($assert) {
                $assert($request);

                return new HttpFulfilledPromise(new Response());
            },
            static function () {
                return new HttpFulfilledPromise(new Response());
            }
        )->wait();
    }
}
