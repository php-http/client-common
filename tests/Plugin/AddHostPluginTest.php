<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class AddHostPluginTest extends TestCase
{
    public function testAddsHostWhenMissing(): void
    {
        $plugin = new AddHostPlugin(new Uri('http://example.com:8000'));
        $request = new Request('GET', '/foo');

        $this->handle($plugin, $request, function (RequestInterface $request): void {
            $this->assertSame('example.com', $request->getUri()->getHost());
            $this->assertSame('http', $request->getUri()->getScheme());
            $this->assertSame(8000, $request->getUri()->getPort());
        });
    }

    public function testReplacesHostWhenConfigured(): void
    {
        $plugin = new AddHostPlugin(new Uri('https://example.com:8443'), ['replace' => true]);
        $request = new Request('GET', 'http://old.com/path');

        $this->handle($plugin, $request, function (RequestInterface $request): void {
            $this->assertSame('example.com', $request->getUri()->getHost());
            $this->assertSame('https', $request->getUri()->getScheme());
            $this->assertSame(8443, $request->getUri()->getPort());
        });
    }

    public function testDoesNothingWhenHostAlreadyPresent(): void
    {
        $plugin = new AddHostPlugin(new Uri('https://example.com:443'));
        $request = new Request('GET', 'http://existing.com/foo');

        $this->handle($plugin, $request, function (RequestInterface $request) {
            $this->assertSame('existing.com', $request->getUri()->getHost());
        });
    }

    private function handle(AddHostPlugin $plugin, RequestInterface $request, callable $assert): void
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
