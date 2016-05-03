<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Exception;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RetryPluginSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\RetryPlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_returns_response(RequestInterface $request, ResponseInterface $response)
    {
        $next = function (RequestInterface $receivedRequest) use($request, $response) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new FulfilledPromise($response->getWrappedObject());
            }
        };

        $this->handleRequest($request, $next, function () {})->shouldReturnAnInstanceOf('Http\Promise\FulfilledPromise');
    }

    function it_throws_exception_on_multiple_exceptions(RequestInterface $request)
    {
        $exception1 = new Exception\NetworkException('Exception 1', $request->getWrappedObject());
        $exception2 = new Exception\NetworkException('Exception 2', $request->getWrappedObject());

        $count = 0;
        $next  = function (RequestInterface $receivedRequest) use($request, $exception1, $exception2, &$count) {
            $count++;
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                if ($count == 1) {
                    return new RejectedPromise($exception1);
                }

                if ($count == 2) {
                    return new RejectedPromise($exception2);
                }
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $promise->shouldThrow($exception2)->duringWait();
    }

    function it_returns_response_on_second_try(RequestInterface $request, ResponseInterface $response)
    {
        $exception = new Exception\NetworkException('Exception 1', $request->getWrappedObject());

        $count = 0;
        $next  = function (RequestInterface $receivedRequest) use($request, $exception, $response, &$count) {
            $count++;
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                if ($count == 1) {
                    return new RejectedPromise($exception);
                }

                if ($count == 2) {
                    return new FulfilledPromise($response->getWrappedObject());
                }
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Promise\FulfilledPromise');
        $promise->wait()->shouldReturn($response);
    }

    function it_does_not_keep_history_of_old_failure(RequestInterface $request, ResponseInterface $response)
    {
        $exception = new Exception\NetworkException('Exception 1', $request->getWrappedObject());

        $count = 0;
        $next  = function (RequestInterface $receivedRequest) use($request, $exception, $response, &$count) {
            $count++;
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                if ($count % 2 == 1) {
                    return new RejectedPromise($exception);
                }

                if ($count % 2 == 0) {
                    return new FulfilledPromise($response->getWrappedObject());
                }
            }
        };

        $this->handleRequest($request, $next, function () {})->shouldReturnAnInstanceOf('Http\Promise\FulfilledPromise');
        $this->handleRequest($request, $next, function () {})->shouldReturnAnInstanceOf('Http\Promise\FulfilledPromise');
    }
}
