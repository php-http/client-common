<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\RequestMatcherPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Message\RequestMatcher;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RequestMatcherPluginTest extends TestCase
{
    public function testDelegatesToSuccessPluginWhenMatched(): void
    {
        $matcher = $this->createMock(RequestMatcher::class);
        $success = $this->createMock(Plugin::class);
        $request = new Request('GET', 'https://example.com');
        $plugin = new RequestMatcherPlugin($matcher, $success);

        $matcher->expects($this->once())->method('matches')->with($request)->willReturn(true);
        $success->expects($this->once())->method('handleRequest')->with($request)->willReturn(new HttpFulfilledPromise(new Response()));

        $plugin->handleRequest($request, function () {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        });
    }

    public function testDelegatesToFailurePluginWhenNotMatched(): void
    {
        $matcher = $this->createMock(RequestMatcher::class);
        $failure = $this->createMock(Plugin::class);
        $request = new Request('GET', 'https://example.com');
        $plugin = new RequestMatcherPlugin($matcher, null, $failure);

        $matcher->expects($this->once())->method('matches')->with($request)->willReturn(false);
        $failure->expects($this->once())->method('handleRequest')->with($request)->willReturn(new HttpFulfilledPromise(new Response()));

        $plugin->handleRequest($request, function () {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        });
    }

    public function testCallsNextWhenNoDelegateMatches(): void
    {
        $matcher = $this->createMock(RequestMatcher::class);
        $request = new Request('GET', 'https://example.com');
        $plugin = new RequestMatcherPlugin($matcher, null, null);

        $matcher->expects($this->once())->method('matches')->willReturn(false);

        $result = $plugin->handleRequest($request, function () {
            return new HttpFulfilledPromise(new Response());
        }, static function () {
        })->wait();

        $this->assertInstanceOf(Response::class, $result);
    }
}
