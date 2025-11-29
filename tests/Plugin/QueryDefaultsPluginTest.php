<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\QueryDefaultsPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class QueryDefaultsPluginTest extends TestCase
{
    public function testAddsMissingQueryParameters(): void
    {
        $plugin = new QueryDefaultsPlugin(['foo' => 'bar']);
        $request = new Request('GET', 'https://example.com/?test=true');

        $this->handle($plugin, $request, function (RequestInterface $request) {
            $this->assertSame('test=true&foo=bar', $request->getUri()->getQuery());
        });
    }

    public function testDoesNotOverrideExistingParameters(): void
    {
        $plugin = new QueryDefaultsPlugin(['foo' => 'fooDefault', 'bar' => 'barDefault']);
        $request = new Request('GET', 'https://example.com/?foo=new');

        $this->handle($plugin, $request, function (RequestInterface $request) {
            $this->assertSame('foo=new&bar=barDefault', $request->getUri()->getQuery());
        });
    }

    private function handle(QueryDefaultsPlugin $plugin, RequestInterface $request, callable $assert): void
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
