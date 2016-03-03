<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Promise\FulfilledPromise;
use Http\Message\Cookie;
use Http\Message\CookieJar;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CookiePluginSpec extends ObjectBehavior
{
    private $cookieJar;

    function let()
    {
        $this->cookieJar = new CookieJar();

        $this->beConstructedWith($this->cookieJar);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\CookiePlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_loads_cookie(RequestInterface $request, UriInterface $uri, Promise $promise)
    {
        $cookie = new Cookie('name', 'value', 86400, 'test.com');
        $this->cookieJar->addCookie($cookie);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');
        $uri->getPath()->willReturn('/');

        $request->withAddedHeader('Cookie', 'name=value')->willReturn($request);

        $this->handleRequest($request, function (RequestInterface $requestReceived) use ($request, $promise) {
            if (Argument::is($requestReceived)->scoreArgument($request->getWrappedObject())) {
                return $promise->getWrappedObject();
            }
        }, function () {});
    }

    function it_does_not_load_cookie_if_expired(RequestInterface $request, UriInterface $uri, Promise $promise)
    {
        $cookie = new Cookie('name', 'value', null, 'test.com', false, false, null, (new \DateTime())->modify('-1 day'));
        $this->cookieJar->addCookie($cookie);

        $request->withAddedHeader('Cookie', 'name=value')->shouldNotBeCalled();

        $this->handleRequest($request, function (RequestInterface $requestReceived) use ($request, $promise) {
            if (Argument::is($requestReceived)->scoreArgument($request->getWrappedObject())) {
                return $promise->getWrappedObject();
            }
        }, function () {});
    }

    function it_does_not_load_cookie_if_domain_does_not_match(RequestInterface $request, UriInterface $uri, Promise $promise)
    {
        $cookie = new Cookie('name', 'value', 86400, 'test2.com');
        $this->cookieJar->addCookie($cookie);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');

        $request->withAddedHeader('Cookie', 'name=value')->shouldNotBeCalled();

        $this->handleRequest($request, function (RequestInterface $requestReceived) use ($request, $promise) {
            if (Argument::is($requestReceived)->scoreArgument($request->getWrappedObject())) {
                return $promise->getWrappedObject();
            }
        }, function () {});
    }

    function it_does_not_load_cookie_if_path_does_not_match(RequestInterface $request, UriInterface $uri, Promise $promise)
    {
        $cookie = new Cookie('name', 'value', 86400, 'test.com', '/sub');
        $this->cookieJar->addCookie($cookie);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');
        $uri->getPath()->willReturn('/');

        $request->withAddedHeader('Cookie', 'name=value')->shouldNotBeCalled();

        $this->handleRequest($request, function (RequestInterface $requestReceived) use ($request, $promise) {
            if (Argument::is($requestReceived)->scoreArgument($request->getWrappedObject())) {
                return $promise->getWrappedObject();
            }
        }, function () {});
    }

    function it_does_not_load_cookie_when_cookie_is_secure(RequestInterface $request, UriInterface $uri, Promise $promise)
    {
        $cookie = new Cookie('name', 'value', 86400, 'test.com', null, true);
        $this->cookieJar->addCookie($cookie);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');
        $uri->getPath()->willReturn('/');
        $uri->getScheme()->willReturn('http');

        $request->withAddedHeader('Cookie', 'name=value')->shouldNotBeCalled();

        $this->handleRequest($request, function (RequestInterface $requestReceived) use ($request, $promise) {
            if (Argument::is($requestReceived)->scoreArgument($request->getWrappedObject())) {
                return $promise->getWrappedObject();
            }
        }, function () {});
    }

    function it_loads_cookie_when_cookie_is_secure(RequestInterface $request, UriInterface $uri, Promise $promise)
    {
        $cookie = new Cookie('name', 'value', 86400, 'test.com', null, true);
        $this->cookieJar->addCookie($cookie);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');
        $uri->getPath()->willReturn('/');
        $uri->getScheme()->willReturn('https');

        $request->withAddedHeader('Cookie', 'name=value')->willReturn($request);

        $this->handleRequest($request, function (RequestInterface $requestReceived) use ($request, $promise) {
            if (Argument::is($requestReceived)->scoreArgument($request->getWrappedObject())) {
                return $promise->getWrappedObject();
            }
        }, function () {});
    }

    function it_saves_cookie(RequestInterface $request, ResponseInterface $response, UriInterface $uri)
    {
        $next = function () use ($response) {
            return new FulfilledPromise($response->getWrappedObject());
        };

        $response->hasHeader('Set-Cookie')->willReturn(true);
        $response->getHeader('Set-Cookie')->willReturn([
            'cookie=value; expires=Tuesday, 31-Mar-99 07:42:12 GMT; Max-Age=60; path=/; domain=test.com; secure; HttpOnly'
        ]);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');
        $uri->getPath()->willReturn('/');

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldHaveType('Http\Promise\Promise');
        $promise->wait()->shouldReturnAnInstanceOf('Psr\Http\Message\ResponseInterface');
    }

    function it_throws_exception_on_invalid_expires_date(
        RequestInterface $request,
        ResponseInterface $response,
        UriInterface $uri
    ) {
        $next = function () use ($response) {
            return new FulfilledPromise($response->getWrappedObject());
        };

        $response->hasHeader('Set-Cookie')->willReturn(true);
        $response->getHeader('Set-Cookie')->willReturn([
            'cookie=value; expires=i-am-an-invalid-date;'
        ]);

        $request->getUri()->willReturn($uri);
        $uri->getHost()->willReturn('test.com');
        $uri->getPath()->willReturn('/');

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow('Http\Client\Exception\TransferException')->duringWait();
    }
}
