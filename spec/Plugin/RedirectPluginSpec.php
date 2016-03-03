<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RedirectPluginSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\RedirectPlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_redirects_on_302(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getRequestTarget()->willReturn('/original');
        $request->getUri()->willReturn($uri);
        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');

        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $finalPromise = $this->handleRequest($request, $next, $first);
        $finalPromise->shouldReturnAnInstanceOf('Http\Promise\FulfilledPromise');
        $finalPromise->wait()->shouldReturn($finalResponse);
    }

    function it_use_storage_on_301(UriInterface $uriRedirect, RequestInterface $request, RequestInterface $modifiedRequest)
    {
        $this->beAnInstanceOf('spec\Http\Client\Common\Plugin\RedirectPluginStub');
        $this->beConstructedWith($uriRedirect, '/original', '301');

        $next = function () {
            throw new \Exception('Must not be called');
        };

        $request->getRequestTarget()->willReturn('/original');
        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');

        $this->handleRequest($request, $next, function () {});
    }

    function it_stores_a_301(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {

        $this->beAnInstanceOf('spec\Http\Client\Common\Plugin\RedirectPluginStub');
        $this->beConstructedWith($uriRedirect, '', '301');

        $request->getRequestTarget()->willReturn('/301-url');
        $request->getUri()->willReturn($uri);

        $responseRedirect->getStatusCode()->willReturn('301');
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');

        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
        $this->hasStorage('/301-url')->shouldReturn(true);
    }

    function it_replace_full_url(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $request->getRequestTarget()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('https://server.com:8000/redirect?query#fragment');

        $request->getUri()->willReturn($uri);
        $uri->withScheme('https')->willReturn($uriRedirect);
        $uriRedirect->withHost('server.com')->willReturn($uriRedirect);
        $uriRedirect->withPort('8000')->willReturn($uriRedirect);
        $uriRedirect->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withQuery('query')->willReturn($uriRedirect);
        $uriRedirect->withFragment('fragment')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');

        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    function it_throws_http_exception_on_no_location(RequestInterface $request, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $request->getRequestTarget()->willReturn('/original');
        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(false);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow('Http\Client\Exception\HttpException')->duringWait();
    }

    function it_throws_http_exception_on_invalid_location(RequestInterface $request, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $request->getRequestTarget()->willReturn('/original');
        $responseRedirect->getHeaderLine('Location')->willReturn('scheme:///invalid');

        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(true);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow('Http\Client\Exception\HttpException')->duringWait();
    }

    function it_throw_multi_redirect_exception_on_300(RequestInterface $request, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $this->beConstructedWith(['preserve_header' => true, 'use_default_for_multiple' => false]);
        $responseRedirect->getStatusCode()->willReturn('300');

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow('Http\Client\Common\Exception\MultipleRedirectionException')->duringWait();
    }

    function it_throw_multi_redirect_exception_on_300_if_no_location(RequestInterface $request, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $responseRedirect->getStatusCode()->willReturn('300');
        $responseRedirect->hasHeader('Location')->willReturn(false);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow('Http\Client\Common\Exception\MultipleRedirectionException')->duringWait();
    }

    function it_switch_method_for_302(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $request->getRequestTarget()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getUri()->willReturn($uri);
        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);
        $modifiedRequest->getUri()->willReturn($uriRedirect);

        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('POST');
        $modifiedRequest->withMethod('GET')->willReturn($modifiedRequest);

        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    function it_clears_headers(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $this->beConstructedWith(['preserve_header' => ['Accept']]);

        $request->getRequestTarget()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getUri()->willReturn($uri);
        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');
        $modifiedRequest->getHeaders()->willReturn(['Accept' => 'value', 'Cookie' => 'value']);
        $modifiedRequest->withoutHeader('Cookie')->willReturn($modifiedRequest);
        $modifiedRequest->getUri()->willReturn($uriRedirect);

        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    function it_throws_circular_redirection_exception(UriInterface $uri, UriInterface $uriRedirect, RequestInterface $request, ResponseInterface $responseRedirect, RequestInterface $modifiedRequest)
    {
        $first = function() {};

        $this->beAnInstanceOf('spec\Http\Client\Common\Plugin\RedirectPluginStubCircular');
        $this->beConstructedWith(spl_object_hash((object)$first));

        $request->getRequestTarget()->willReturn('/original');
        $request->getUri()->willReturn($uri);

        $responseRedirect->getStatusCode()->willReturn('302');
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);
        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $modifiedRequest->getRequestTarget()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');

        $next = function (RequestInterface $receivedRequest) use($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $promise = $this->handleRequest($request, $next, $first);
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow('Http\Client\Common\Exception\CircularRedirectionException')->duringWait();
    }
}

class RedirectPluginStub extends RedirectPlugin
{
    public function __construct(UriInterface $uri, $storedUrl, $status, array $config = [])
    {
        parent::__construct($config);

        $this->redirectStorage[$storedUrl] = [
            'uri' => $uri,
            'status' => $status
        ];
    }

    public function hasStorage($url)
    {
        return isset($this->redirectStorage[$url]);
    }
}

class RedirectPluginStubCircular extends RedirectPlugin
{
    public function __construct($chainHash)
    {
        $this->circularDetection = [
            $chainHash => [
                '/redirect'
            ]
        ];
    }
}
