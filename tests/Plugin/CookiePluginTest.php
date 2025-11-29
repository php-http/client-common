<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\CookiePlugin;
use Http\Client\Exception\TransferException;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use Http\Message\Cookie;
use Http\Message\CookieJar;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CookiePluginTest extends TestCase
{
    public function testLoadsCookie(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com'));

        $plugin = new CookiePlugin($jar);

        $this->assertCookieHeader('name=value', $plugin, new Request('GET', 'http://test.com/'));
    }

    public function testCombinesMultipleCookies(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com'));
        $jar->addCookie(new Cookie('name2', 'value2', 86400, 'test.com'));

        $plugin = new CookiePlugin($jar);

        $this->assertCookieHeader('name=value; name2=value2', $plugin, new Request('GET', 'http://test.com/'));
    }

    public function testSkipsExpiredCookies(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', null, 'test.com', '/', false, false, new \DateTime('-1 day')));

        $plugin = new CookiePlugin($jar);

        $this->assertNoCookieHeader($plugin, new Request('GET', 'http://test.com/'));
    }

    public function testSkipsMismatchedDomain(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test2.com'));

        $plugin = new CookiePlugin($jar);

        $this->assertNoCookieHeader($plugin, new Request('GET', 'http://test.com/'));
    }

    public function testSkipsHackishDomains(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com'));

        $plugin = new CookiePlugin($jar);

        foreach (['hacktest.com', 'test.com.hacked.org'] as $domain) {
            $this->assertNoCookieHeader($plugin, new Request('GET', 'http://'.$domain.'/'));
        }
    }

    public function testLoadsCookieOnSubdomain(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com'));

        $plugin = new CookiePlugin($jar);

        $this->assertCookieHeader('name=value', $plugin, new Request('GET', 'http://www.test.com/'));
    }

    public function testSkipsPathMismatch(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com', '/sub'));
        $plugin = new CookiePlugin($jar);

        $this->assertNoCookieHeader($plugin, new Request('GET', 'http://test.com/'));
    }

    public function testSkipsSecureCookieOnHttp(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com', null, true));

        $plugin = new CookiePlugin($jar);

        $this->assertNoCookieHeader($plugin, new Request('GET', 'http://test.com/'));
    }

    public function testLoadsSecureCookieOnHttps(): void
    {
        $jar = new CookieJar();
        $jar->addCookie(new Cookie('name', 'value', 86400, 'test.com', null, true));

        $plugin = new CookiePlugin($jar);

        $this->assertCookieHeader('name=value', $plugin, new Request('GET', 'https://test.com/'));
    }

    public function testStoresCookieFromResponse(): void
    {
        $jar = new CookieJar();
        $plugin = new CookiePlugin($jar);

        $response = new Response(200, [
            'Set-Cookie' => ['cookie=value; expires=Tue, 31-Mar-99 07:42:12 GMT; Max-Age=60; path=/; domain=test.com; secure; HttpOnly'],
        ]);

        $promise = $plugin->handleRequest(
            new Request('GET', 'http://test.com/'),
            function () use ($response) {
                return new HttpFulfilledPromise($response);
            },
            static function () {
            }
        );

        $this->assertInstanceOf(ResponseInterface::class, $promise->wait());
        $this->assertNotEmpty($jar->getCookies());
    }

    public function testThrowsOnInvalidExpiresDate(): void
    {
        $jar = new CookieJar();
        $plugin = new CookiePlugin($jar);

        $response = new Response(200, [
            'Set-Cookie' => ['cookie=value; expires=i-am-an-invalid-date;'],
        ]);

        $promise = $plugin->handleRequest(
            new Request('GET', 'http://test.com/'),
            function () use ($response) {
                return new HttpFulfilledPromise($response);
            },
            static function () {
            }
        );

        $this->assertInstanceOf(HttpRejectedPromise::class, $promise);
        $this->expectException(TransferException::class);
        $promise->wait();
    }

    private function assertCookieHeader(string $expected, CookiePlugin $plugin, RequestInterface $request): void
    {
        $plugin->handleRequest(
            $request,
            function (RequestInterface $request) use ($expected) {
                $this->assertSame($expected, $request->getHeaderLine('Cookie'));

                return new HttpFulfilledPromise(new Response());
            },
            static function () {
                return new HttpFulfilledPromise(new Response());
            }
        )->wait();
    }

    private function assertNoCookieHeader(CookiePlugin $plugin, RequestInterface $request): void
    {
        $plugin->handleRequest(
            $request,
            function (RequestInterface $request) {
                $this->assertFalse($request->hasHeader('Cookie'));

                return new HttpFulfilledPromise(new Response());
            },
            static function () {
                return new HttpFulfilledPromise(new Response());
            }
        )->wait();
    }
}
