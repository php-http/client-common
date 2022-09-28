<?php

declare(strict_types=1);

namespace tests\Http\Client\Common;

use Http\Client\Common\Exception\LoopException;
use Http\Client\Common\Plugin;
use Http\Client\Common\PluginChain;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class PluginChainTest extends TestCase
{
    private function createPlugin(callable $func): Plugin
    {
        return new class($func) implements Plugin {
            public $func;

            public function __construct(callable $func)
            {
                $this->func = $func;
            }

            public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
            {
                ($this->func)($request, $next, $first);

                return $next($request);
            }
        };
    }

    public function testChainShouldInvokePluginsInReversedOrder(): void
    {
        $pluginOrderCalls = [];

        $plugin1 = $this->createPlugin(static function () use (&$pluginOrderCalls) {
            $pluginOrderCalls[] = 'plugin1';
        });
        $plugin2 = $this->createPlugin(static function () use (&$pluginOrderCalls) {
            $pluginOrderCalls[] = 'plugin2';
        });

        $request = $this->prophesize(RequestInterface::class);
        $responsePromise = $this->prophesize(Promise::class);

        $clientCallable = static function () use ($responsePromise) {
            return $responsePromise->reveal();
        };

        $pluginOrderCalls = [];

        $plugins = [
            $plugin1,
            $plugin2,
        ];

        $pluginChain = new PluginChain($plugins, $clientCallable);

        $result = $pluginChain($request->reveal());

        $this->assertSame($responsePromise->reveal(), $result);
        $this->assertSame(['plugin1', 'plugin2'], $pluginOrderCalls);
    }

    public function testShouldThrowLoopExceptionOnMaxRestarts(): void
    {
        $this->expectException(LoopException::class);

        $request = $this->prophesize(RequestInterface::class);
        $responsePromise = $this->prophesize(Promise::class);
        $calls = 0;
        $clientCallable = static function () use ($responsePromise, &$calls) {
            ++$calls;

            return $responsePromise->reveal();
        };

        $pluginChain = new PluginChain([], $clientCallable, ['max_restarts' => 2]);

        $pluginChain($request->reveal());
        $this->assertSame(1, $calls);
        $pluginChain($request->reveal());
        $this->assertSame(2, $calls);
        $pluginChain($request->reveal());
        $this->assertSame(3, $calls);
        $pluginChain($request->reveal());
    }
}
