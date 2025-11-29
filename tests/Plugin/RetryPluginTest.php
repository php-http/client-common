<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\NetworkException;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryPluginTest extends TestCase
{
    public function testReturnsResponseImmediately(): void
    {
        $plugin = new RetryPlugin();
        $request = new Request('GET', 'https://example.com');
        $response = new Response();

        $result = $plugin->handleRequest($request, function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();

        $this->assertSame($response, $result);
    }

    public function testThrowsLastExceptionAfterRetries(): void
    {
        $request = new Request('GET', 'https://example.com');
        $exception1 = new NetworkException('first', $request);
        $exception2 = new NetworkException('second', $request);
        $count = 0;

        $plugin = new RetryPlugin(['exception_delay' => function () {
            return 0;
        }]);

        $next = function () use (&$count, $exception1, $exception2) {
            ++$count;

            return new HttpRejectedPromise(1 === $count ? $exception1 : $exception2);
        };

        $this->expectExceptionObject($exception2);
        $plugin->handleRequest($request, $next, static function () {
        })->wait();
    }

    public function testDoesNotRetryClientErrors(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response(400);
        $exception = new HttpException('client error', $request, $response);

        $plugin = new RetryPlugin(['exception_delay' => function () {
            return 0;
        }]);

        $this->expectExceptionObject($exception);
        $plugin->handleRequest($request, function () use ($exception) {
            return new HttpRejectedPromise($exception);
        }, static function () {
        })->wait();
    }

    public function testReturnsResponseOnSecondTry(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response();
        $exception = new NetworkException('fail', $request);
        $count = 0;

        $plugin = new RetryPlugin(['exception_delay' => function () {
            return 0;
        }]);

        $result = $plugin->handleRequest(
            $request,
            function () use (&$count, $exception, $response) {
                ++$count;

                return 1 === $count ? new HttpRejectedPromise($exception) : new HttpFulfilledPromise($response);
            },
            static function () {
            }
        )->wait();

        $this->assertSame($response, $result);
    }

    public function testRespectsCustomExceptionDecider(): void
    {
        $request = new Request('GET', 'https://example.com');
        $exception = new NetworkException('fail', $request);

        $plugin = new RetryPlugin([
            'exception_decider' => static function () {
                return false;
            },
        ]);

        $this->expectExceptionObject($exception);
        $plugin->handleRequest($request, function () use ($exception) {
            return new HttpRejectedPromise($exception);
        }, static function () {
        })->wait();
    }

    public function testDoesNotKeepHistoryBetweenRequests(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response();
        $exception = new NetworkException('fail', $request);
        $count = 0;

        $plugin = new RetryPlugin(['exception_delay' => function () {
            return 0;
        }]);

        $next = function () use (&$count, $exception, $response) {
            ++$count;

            return 1 === $count % 2 ? new HttpRejectedPromise($exception) : new HttpFulfilledPromise($response);
        };

        $this->assertSame($response, $plugin->handleRequest($request, $next, static function () {
        })->wait());
        $this->assertSame($response, $plugin->handleRequest($request, $next, static function () {
        })->wait());
    }

    public function testDefaultErrorResponseDelay(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $this->assertSame(500000, RetryPlugin::defaultErrorResponseDelay($request, $response, 0));
        $this->assertSame(1000000, RetryPlugin::defaultErrorResponseDelay($request, $response, 1));
        $this->assertSame(2000000, RetryPlugin::defaultErrorResponseDelay($request, $response, 2));
        $this->assertSame(4000000, RetryPlugin::defaultErrorResponseDelay($request, $response, 3));
    }

    public function testDefaultExceptionDelay(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $exception = $this->createMock(\Psr\Http\Client\ClientExceptionInterface::class);

        $this->assertSame(500000, RetryPlugin::defaultExceptionDelay($request, $exception, 0));
        $this->assertSame(1000000, RetryPlugin::defaultExceptionDelay($request, $exception, 1));
        $this->assertSame(2000000, RetryPlugin::defaultExceptionDelay($request, $exception, 2));
        $this->assertSame(4000000, RetryPlugin::defaultExceptionDelay($request, $exception, 3));
    }
}
