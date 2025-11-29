<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class HeaderAppendPluginTest extends TestCase
{
    public function testAppendsHeaders(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->exactly(2))
            ->method('withAddedHeader')
            ->withConsecutive(['foo', 'bar'], ['baz', 'qux'])
            ->willReturnSelf();

        $plugin = new HeaderAppendPlugin(['foo' => 'bar', 'baz' => 'qux']);

        $plugin->handleRequest($request, static function ($request) {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        });
    }
}
