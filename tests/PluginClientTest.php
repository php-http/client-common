<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\Exception\LoopException;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
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
    public function testItImplementsHttpAndAsyncClients(): void
    {
        $httpClient = $this->createMock(HttpClient::class);

        $pluginClient = new PluginClient($httpClient);

        $this->assertInstanceOf(PluginClient::class, $pluginClient);
        $this->assertInstanceOf(HttpClient::class, $pluginClient);
        $this->assertInstanceOf(HttpAsyncClient::class, $pluginClient);
    }

    public function testSendRequestUsesUnderlyingClient(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $pluginClient = new PluginClient($httpClient);

        $this->assertSame($response, $pluginClient->sendRequest($request));
    }

    public function testSendAsyncRequestUsesUnderlyingClient(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $promise = $this->createMock(Promise::class);

        $httpAsyncClient = $this->createMock(HttpAsyncClient::class);
        $httpAsyncClient
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->with($request)
            ->willReturn($promise);

        $pluginClient = new PluginClient($httpAsyncClient);

        $this->assertSame($promise, $pluginClient->sendAsyncRequest($request));
    }

    public function testSendRequestFallsBackToAsyncClient(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $promise = $this->createMock(Promise::class);

        $httpAsyncClient = $this->createMock(HttpAsyncClient::class);
        $httpAsyncClient
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->with($request)
            ->willReturn($promise);

        $promise
            ->expects($this->once())
            ->method('wait')
            ->willReturn($response);

        $pluginClient = new PluginClient($httpAsyncClient);

        $this->assertSame($response, $pluginClient->sendRequest($request));
    }

    public function testSendRequestPrefersSynchronousCallWhenAvailable(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $client = new class($response) implements HttpClient, HttpAsyncClient {
            public $syncCalls = 0;
            public $asyncCalls = 0;
            private $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                ++$this->syncCalls;

                return $this->response;
            }

            public function sendAsyncRequest(RequestInterface $request)
            {
                ++$this->asyncCalls;

                return new HttpFulfilledPromise($this->response);
            }
        };

        $pluginClient = new PluginClient($client);

        $this->assertSame($response, $pluginClient->sendRequest($request));
        $this->assertSame(1, $client->syncCalls);
        $this->assertSame(0, $client->asyncCalls);
    }

    public function testLoopDetectionThrowsException(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $request = $this->createMock(RequestInterface::class);

        $loopPlugin = new class implements Plugin {
            public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
            {
                return $first($request);
            }
        };

        $pluginClient = new PluginClient($httpClient, [$loopPlugin]);

        $this->expectException(LoopException::class);
        $pluginClient->sendRequest($request);
    }

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
        $syncClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $asyncClient = new class implements HttpAsyncClient {
            public function sendAsyncRequest(RequestInterface $request)
            {
                return new HttpFulfilledPromise(new Response());
            }
        };

        $headerAppendPlugin = new HeaderAppendPlugin(['Content-Type' => 'text/html']);
        $redirectPlugin = new RedirectPlugin();
        $restartOncePlugin = new class implements Plugin {
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
