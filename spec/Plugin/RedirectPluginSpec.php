<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Exception\CircularRedirectionException;
use Http\Client\Common\Exception\MultipleRedirectionException;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Exception\HttpException;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use Http\Promise\Promise;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class RedirectPluginSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RedirectPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_redirects_on_302(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getUri()->willReturn($uri);
        $request->withUri($uriRedirect)->willReturn($modifiedRequest);
        $uri->__toString()->willReturn('/original');

        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);
        $uriRedirect->__toString()->willReturn('/redirect');

        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $modifiedRequest->getMethod()->willReturn('GET');

        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use ($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $finalPromise = $this->handleRequest($request, $next, $first);
        $finalPromise->shouldReturnAnInstanceOf(HttpFulfilledPromise::class);
        $finalPromise->wait()->shouldReturn($finalResponse);
    }

    public function it_use_storage_on_301(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        ResponseInterface $redirectResponse
    ) {
        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');
        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $modifiedRequest->getMethod()->willReturn('GET');

        $uriRedirect->__toString()->willReturn('/redirect');

        $finalResponse->getStatusCode()->willReturn(200);

        $redirectResponse->getStatusCode()->willReturn(301);
        $redirectResponse->hasHeader('Location')->willReturn(true);
        $redirectResponse->getHeaderLine('Location')->willReturn('/redirect');

        $nextCalled = false;
        $next = function (RequestInterface $request) use (&$nextCalled, $finalResponse, $redirectResponse): Promise {
            switch ($request->getUri()) {
                case '/original':
                    if ($nextCalled) {
                        throw new \Exception('Must only be called once');
                    }
                    $nextCalled = true;

                    return new HttpFulfilledPromise($redirectResponse->getWrappedObject());
                case '/redirect':

                    return new HttpFulfilledPromise($finalResponse->getWrappedObject());
                default:
                    throw new \Exception('Test setup error with request uri '.$request->getUri());
            }
        };
        $first = $this->buildFirst($modifiedRequest, $next);

        $this->handleRequest($request, $next, $first);

        // rebuild first as this is expected to be called again
        $first = $this->buildFirst($modifiedRequest, $next);
        // next should not be called again
        $this->handleRequest($request, $next, $first);
    }

    private function buildFirst(RequestInterface $modifiedRequest, callable $next): callable
    {
        $redirectPlugin = $this;
        $firstCalled = false;

        return function (RequestInterface $request) use (&$modifiedRequest, $redirectPlugin, $next, &$firstCalled) {
            if ($firstCalled) {
                throw new \Exception('Only one restart expected');
            }
            $firstCalled = true;
            if ($modifiedRequest->getWrappedObject() !== $request) {
                //throw new \Exception('Redirection failed');
            }

            return $redirectPlugin->getWrappedObject()->handleRequest($request, $next, $this);
        };
    }

    public function it_replace_full_url(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn(302);
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

        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $modifiedRequest->getMethod()->willReturn('GET');

        $uriRedirect->__toString()->willReturn('/redirect');

        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use ($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    public function it_throws_http_exception_on_no_location(RequestInterface $request, UriInterface $uri, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');
        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(false);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(HttpException::class)->duringWait();
    }

    public function it_throws_http_exception_on_invalid_location(RequestInterface $request, UriInterface $uri, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');
        $responseRedirect->getHeaderLine('Location')->willReturn('scheme:///invalid');

        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(true);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(HttpException::class)->duringWait();
    }

    public function it_throw_multi_redirect_exception_on_300(RequestInterface $request, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $this->beConstructedWith(['preserve_header' => true, 'use_default_for_multiple' => false]);
        $responseRedirect->getStatusCode()->willReturn(300);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(MultipleRedirectionException::class)->duringWait();
    }

    public function it_throw_multi_redirect_exception_on_300_if_no_location(RequestInterface $request, ResponseInterface $responseRedirect)
    {
        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $responseRedirect->getStatusCode()->willReturn(300);
        $responseRedirect->hasHeader('Location')->willReturn(false);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(MultipleRedirectionException::class)->duringWait();
    }

    public function it_switch_method_for_302(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getUri()->willReturn($uri);
        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);
        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $uriRedirect->__toString()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('POST');
        $modifiedRequest->withMethod('GET')->shouldBeCalled()->willReturn($modifiedRequest);

        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use ($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    public function it_does_not_switch_method_for_302_with_strict_option(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $this->beConstructedWith(['strict' => true]);

        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getUri()->willReturn($uri);
        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);
        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $uriRedirect->__toString()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('POST');
        $modifiedRequest->withMethod('GET')->shouldNotBeCalled();

        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use ($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    public function it_clears_headers(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $this->beConstructedWith(['preserve_header' => ['Accept']]);

        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');

        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('/redirect');

        $request->getUri()->willReturn($uri);
        $uri->withPath('/redirect')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);

        $request->withUri($uriRedirect)->willReturn($modifiedRequest);

        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $uriRedirect->__toString()->willReturn('/redirect');
        $modifiedRequest->getMethod()->willReturn('GET');
        $modifiedRequest->getHeaders()->willReturn(['Accept' => 'value', 'Cookie' => 'value']);
        $modifiedRequest->withoutHeader('Cookie')->willReturn($modifiedRequest);
        $modifiedRequest->getUri()->willReturn($uriRedirect);

        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use ($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $this->handleRequest($request, $next, $first);
    }

    /**
     * This is the "redirection does not redirect case.
     */
    public function it_throws_circular_redirection_exception_on_redirect_that_does_not_change_url(
        UriInterface $redirectUri,
        RequestInterface $request,
        ResponseInterface $redirectResponse
    ) {
        $redirectResponse->getStatusCode()->willReturn(302);
        $redirectResponse->hasHeader('Location')->willReturn(true);
        $redirectResponse->getHeaderLine('Location')->willReturn('/redirect');

        $next = function () use ($redirectResponse): Promise {
            return new HttpFulfilledPromise($redirectResponse->getWrappedObject());
        };

        $first = function () {
            throw new \Exception('First should never be called');
        };

        $request->getUri()->willReturn($redirectUri);
        $redirectUri->__toString()->willReturn('/redirect');

        $redirectUri->withPath('/redirect')->willReturn($redirectUri);
        $redirectUri->withFragment('')->willReturn($redirectUri);
        $redirectUri->withQuery('')->willReturn($redirectUri);

        $request->withUri($redirectUri)->willReturn($request);
        $redirectUri->__toString()->willReturn('/redirect');
        $request->getMethod()->willReturn('GET');

        $promise = $this->handleRequest($request, $next, $first);
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(CircularRedirectionException::class)->duringWait();
    }

    /**
     * This is a redirection flipping back and forth between two paths.
     *
     * There could be a larger loop but the logic in the plugin stays the same with as many redirects as needed.
     */
    public function it_throws_circular_redirection_exception_on_alternating_redirect(
        UriInterface $uri,
        UriInterface $redirectUri,
        RequestInterface $request,
        ResponseInterface $redirectResponse1,
        ResponseInterface $redirectResponse2,
        RequestInterface $modifiedRequest
    ) {
        $redirectResponse1->getStatusCode()->willReturn(302);
        $redirectResponse1->hasHeader('Location')->willReturn(true);
        $redirectResponse1->getHeaderLine('Location')->willReturn('/redirect');

        $redirectResponse2->getStatusCode()->willReturn(302);
        $redirectResponse2->hasHeader('Location')->willReturn(true);
        $redirectResponse2->getHeaderLine('Location')->willReturn('/original');

        $next = function (RequestInterface $currentRequest) use ($request, $redirectResponse1, $redirectResponse2): Promise {
            return ($currentRequest === $request->getWrappedObject())
                ? new HttpFulfilledPromise($redirectResponse1->getWrappedObject())
                : new HttpFulfilledPromise($redirectResponse2->getWrappedObject())
            ;
        };

        $redirectPlugin = $this;
        $firstCalled = false;
        $first = function (RequestInterface $request) use (&$firstCalled, $redirectPlugin, $next, &$first) {
            if ($firstCalled) {
                throw new \Exception('only one redirect expected');
            }
            $firstCalled = true;

            return $redirectPlugin->getWrappedObject()->handleRequest($request, $next, $first);
        };

        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('/original');

        $modifiedRequest->getUri()->willReturn($redirectUri);
        $redirectUri->__toString()->willReturn('/redirect');

        $uri->withPath('/redirect')->willReturn($redirectUri);
        $redirectUri->withFragment('')->willReturn($redirectUri);
        $redirectUri->withQuery('')->willReturn($redirectUri);

        $redirectUri->withPath('/original')->willReturn($uri);
        $uri->withFragment('')->willReturn($uri);
        $uri->withQuery('')->willReturn($uri);

        $request->withUri($redirectUri)->willReturn($modifiedRequest);
        $request->getMethod()->willReturn('GET');
        $modifiedRequest->withUri($uri)->willReturn($request);
        $modifiedRequest->getMethod()->willReturn('GET');

        $promise = $this->handleRequest($request, $next, $first);
        $promise->shouldReturnAnInstanceOf(HttpRejectedPromise::class);
        $promise->shouldThrow(CircularRedirectionException::class)->duringWait();
    }

    public function it_redirects_http_to_https(
        UriInterface $uri,
        UriInterface $uriRedirect,
        RequestInterface $request,
        ResponseInterface $responseRedirect,
        RequestInterface $modifiedRequest,
        ResponseInterface $finalResponse,
        Promise $promise
    ) {
        $responseRedirect->getStatusCode()->willReturn(302);
        $responseRedirect->hasHeader('Location')->willReturn(true);
        $responseRedirect->getHeaderLine('Location')->willReturn('https://my-site.com/original');

        $request->getUri()->willReturn($uri);
        $request->withUri($uriRedirect)->willReturn($modifiedRequest);
        $uri->__toString()->willReturn('http://my-site.com/original');

        $uri->withScheme('https')->willReturn($uriRedirect);
        $uriRedirect->withHost('my-site.com')->willReturn($uriRedirect);
        $uriRedirect->withPath('/original')->willReturn($uriRedirect);
        $uriRedirect->withFragment('')->willReturn($uriRedirect);
        $uriRedirect->withQuery('')->willReturn($uriRedirect);
        $uriRedirect->__toString()->willReturn('https://my-site.com/original');

        $modifiedRequest->getUri()->willReturn($uriRedirect);
        $modifiedRequest->getMethod()->willReturn('GET');

        $next = function (RequestInterface $receivedRequest) use ($request, $responseRedirect) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($responseRedirect->getWrappedObject());
            }
        };

        $first = function (RequestInterface $receivedRequest) use ($modifiedRequest, $promise) {
            if (Argument::is($modifiedRequest->getWrappedObject())->scoreArgument($receivedRequest)) {
                return $promise->getWrappedObject();
            }
        };

        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->shouldBeCalled()->willReturn($finalResponse);

        $finalPromise = $this->handleRequest($request, $next, $first);
        $finalPromise->shouldReturnAnInstanceOf(HttpFulfilledPromise::class);
        $finalPromise->wait()->shouldReturn($finalResponse);
    }
}
