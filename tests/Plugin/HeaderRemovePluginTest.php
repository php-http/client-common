<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\HeaderRemovePlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class HeaderRemovePluginTest extends TestCase
{
    public function testRemovesHeaders(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->exactly(2))
            ->method('hasHeader')
            ->withConsecutive(['foo'], ['baz'])
            ->willReturnOnConsecutiveCalls(false, true);
        $request->expects($this->once())->method('withoutHeader')->with('baz')->willReturnSelf();

        $plugin = new HeaderRemovePlugin(['foo', 'baz']);

        $plugin->handleRequest($request, static function ($request) {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        });
    }
}
