<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Exception;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
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
                return new HttpFulfilledPromise($response->getWrappedObject());
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Client\Common\Deferred');
        $promise->wait()->shouldReturn($response);
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
                    return new HttpRejectedPromise($exception1);
                }

                if ($count == 2) {
                    return new HttpRejectedPromise($exception2);
                }
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Client\Common\Deferred');
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
                    return new HttpRejectedPromise($exception);
                }

                if ($count == 2) {
                    return new HttpFulfilledPromise($response->getWrappedObject());
                }
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Client\Common\Deferred');
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
                    return new HttpRejectedPromise($exception);
                }

                if ($count % 2 == 0) {
                    return new HttpFulfilledPromise($response->getWrappedObject());
                }
            }
        };

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Client\Common\Deferred');
        $promise->wait()->shouldReturn($response);

        $promise = $this->handleRequest($request, $next, function () {});
        $promise->shouldReturnAnInstanceOf('Http\Client\Common\Deferred');
        $promise->wait()->shouldReturn($response);
    }

    function it_has_an_exponential_default_delay(RequestInterface $request, Exception\HttpException $exception)
    {
        $this->defaultDelay($request, $exception, 0)->shouldBe(500000);
        $this->defaultDelay($request, $exception, 1)->shouldBe(1000000);
        $this->defaultDelay($request, $exception, 2)->shouldBe(2000000);
        $this->defaultDelay($request, $exception, 3)->shouldBe(4000000);
    }
}
