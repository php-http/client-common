<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\Exception\ServerErrorException;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Promise\HttpFulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ErrorPluginTest extends TestCase
{
    public function testThrowsClientErrorOn4xx(): void
    {
        $plugin = new ErrorPlugin();
        $request = new Request('GET', 'https://example.com');
        $response = new Response(400, [], null, '1.1', 'Bad request');

        $this->expectException(ClientErrorException::class);

        $plugin->handleRequest($request, function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();
    }

    public function testDoesNotThrowClientErrorWhenConfigured(): void
    {
        $plugin = new ErrorPlugin(['only_server_exception' => true]);
        $request = new Request('GET', 'https://example.com');
        $response = new Response(400, [], null, '1.1', 'Bad request');

        $result = $plugin->handleRequest($request, function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();

        $this->assertSame($response, $result);
    }

    public function testThrowsServerErrorOn5xx(): void
    {
        $plugin = new ErrorPlugin();
        $request = new Request('GET', 'https://example.com');
        $response = new Response(500, [], null, '1.1', 'Server error');

        $this->expectException(ServerErrorException::class);

        $plugin->handleRequest($request, function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();
    }

    public function testReturnsResponseOtherwise(): void
    {
        $plugin = new ErrorPlugin();
        $request = new Request('GET', 'https://example.com');
        $response = new Response(200);

        $result = $plugin->handleRequest($request, function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();

        $this->assertSame($response, $result);
    }
}
