<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\HeaderSetPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class HeaderSetPluginTest extends TestCase
{
    public function testSetsHeaders(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->exactly(2))
            ->method('withHeader')
            ->withConsecutive(['foo', 'bar'], ['baz', 'qux'])
            ->willReturnSelf();

        $plugin = new HeaderSetPlugin(['foo' => 'bar', 'baz' => 'qux']);

        $plugin->handleRequest($request, static function ($request) {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        });
    }
}
