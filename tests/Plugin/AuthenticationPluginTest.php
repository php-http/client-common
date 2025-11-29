<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class AuthenticationPluginTest extends TestCase
{
    public function testAuthenticatesBeforeDelegating(): void
    {
        $authentication = $this->createMock(Authentication::class);
        $request = $this->createMock(RequestInterface::class);
        $authenticated = $this->createMock(RequestInterface::class);
        $promise = $this->createMock(Promise::class);

        $authentication->expects($this->once())->method('authenticate')->with($request)->willReturn($authenticated);

        $plugin = new AuthenticationPlugin($authentication);

        $next = function (RequestInterface $actual) use ($authenticated, $promise) {
            $this->assertSame($authenticated, $actual);

            return $promise;
        };

        $this->assertSame($promise, $plugin->handleRequest($request, $next, static function () {}));
    }
}
