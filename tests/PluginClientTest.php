<?php

declare(strict_types=1);

namespace tests\Http\Client\Common;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Promise\Promise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PluginClientTest extends TestCase
{
    /**
     * @dataProvider clientAndMethodProvider
     */
    public function testRestartChain(PluginClient $client, string $method, string $returnType)
    {
        $request = new Request('GET', 'https://example.com');
        $result = call_user_func([$client, $method], $request);

        $this->assertInstanceOf($returnType, $result);
    }

    public function clientAndMethodProvider()
    {
        $syncClient = new class() implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $asyncClient = new class() implements HttpAsyncClient {
            public function sendAsyncRequest(RequestInterface $request)
            {
                return new HttpFulfilledPromise(new Response());
            }
        };

        $headerAppendPlugin = new HeaderAppendPlugin(['Content-Type' => 'text/html']);
        $redirectPlugin = new RedirectPlugin();
        $restartOncePlugin = new class() implements Plugin {
            private $firstRun = true;

            public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
            {
                if ($this->firstRun) {
                    $this->firstRun = false;

                    return $first($request);
                }
                $this->firstRun = true;

                return $next($request);
            }
        };

        $plugins = [$headerAppendPlugin, $restartOncePlugin, $redirectPlugin];

        $pluginClient = new PluginClient($syncClient, $plugins);
        yield [$pluginClient, 'sendRequest', ResponseInterface::class];
        yield [$pluginClient, 'sendAsyncRequest', Promise::class];

        // Async
        $pluginClient = new PluginClient($asyncClient, $plugins);
        yield [$pluginClient, 'sendRequest', ResponseInterface::class];
        yield [$pluginClient, 'sendAsyncRequest', Promise::class];
    }
}
