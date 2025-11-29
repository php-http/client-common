<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class HeaderDefaultsPluginTest extends TestCase
{
    public function testSetsDefaultsWhenMissing(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->exactly(2))
            ->method('hasHeader')
            ->withConsecutive(['foo'], ['baz'])
            ->willReturnOnConsecutiveCalls(false, true);
        $request->expects($this->once())
            ->method('withHeader')
            ->with('foo', 'bar')
            ->willReturnSelf();

        $plugin = new HeaderDefaultsPlugin(['foo' => 'bar', 'baz' => 'qux']);

        $plugin->handleRequest($request, static function ($request) {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        });
    }
}
