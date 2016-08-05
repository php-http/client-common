<?php

namespace spec\Http\Client\Common;

use Http\Client\Exception;
use Http\Client\Exception\TransferException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientPoolItemSpec extends ObjectBehavior
{
    public function let(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    public function it_sends_request(HttpClient $httpClient, RequestInterface $request, ResponseInterface $response)
    {
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    public function it_sends_async_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $this->beConstructedWith($httpAsyncClient);

        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);
        $promise->then(Argument::type('callable'), Argument::type('callable'))->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }

    public function it_disable_himself_on_send_request(HttpClient $httpClient, RequestInterface $request)
    {
        $exception = new TransferException();
        $httpClient->sendRequest($request)->willThrow($exception);
        $this->shouldThrow($exception)->duringSendRequest($request);
        $this->isDisabled()->shouldReturn(true);
        $this->shouldThrow('Http\Client\Exception\RequestException')->duringSendRequest($request);
    }

    public function it_disable_himself_on_send_async_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request)
    {
        $this->beConstructedWith($httpAsyncClient);

        $promise = new RejectedPromise(new TransferException());
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $this->isDisabled()->shouldReturn(true);
        $this->shouldThrow('Http\Client\Exception\RequestException')->duringSendAsyncRequest($request);
    }

    public function it_reactivate_himself_on_send_request(HttpClient $httpClient, RequestInterface $request)
    {
        $this->beConstructedWith($httpClient, 0);

        $exception = new TransferException();
        $httpClient->sendRequest($request)->willThrow($exception);

        $this->shouldThrow($exception)->duringSendRequest($request);
        $this->isDisabled()->shouldReturn(false);
        $this->shouldThrow($exception)->duringSendRequest($request);
    }

    public function it_reactivate_himself_on_send_async_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request)
    {
        $this->beConstructedWith($httpAsyncClient, 0);

        $promise = new RejectedPromise(new TransferException());
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
        $this->isDisabled()->shouldReturn(false);
        $this->sendAsyncRequest($request)->shouldReturnAnInstanceOf('Http\Promise\RejectedPromise');
    }

    public function it_increments_request_count(HttpAsyncClient $httpAsyncClient, RequestInterface $request, ResponseInterface $response)
    {
        $this->beConstructedWith($httpAsyncClient, 0);

        $promise = new NotResolvingPromise($response->getWrappedObject());
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->getSendingRequestCount()->shouldReturn(0);
        $this->sendAsyncRequest($request)->shouldReturn($promise);
        $this->getSendingRequestCount()->shouldReturn(1);
        $this->sendAsyncRequest($request)->shouldReturn($promise);
        $this->getSendingRequestCount()->shouldReturn(2);
    }

    public function it_decrements_request_count(HttpAsyncClient $httpAsyncClient, RequestInterface $request, ResponseInterface $response)
    {
        $this->beConstructedWith($httpAsyncClient, 0);

        $promise = new NotResolvingPromise($response->getWrappedObject());
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->getSendingRequestCount()->shouldReturn(0);
        $this->sendAsyncRequest($request)->shouldReturn($promise);
        $this->getSendingRequestCount()->shouldReturn(1);

        $promise->wait(false);

        $this->getSendingRequestCount()->shouldReturn(0);
    }
}

class NotResolvingPromise implements Promise
{
    private $queue = [];

    private $state = Promise::PENDING;

    private $response;

    private $exception;

    public function __construct(ResponseInterface $response = null, Exception $exception = null)
    {
        $this->response = $response;
        $this->exception = $exception;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        $this->queue[] = [
            $onFulfilled,
            $onRejected,
        ];

        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function wait($unwrap = true)
    {
        if ($this->state === Promise::FULFILLED) {
            if (!$unwrap) {
                return;
            }

            return $this->response;
        }

        if ($this->state === Promise::REJECTED) {
            if (!$unwrap) {
                return;
            }

            throw $this->exception;
        }

        while (count($this->queue) > 0) {
            $callbacks = array_shift($this->queue);

            if ($this->response !== null) {
                try {
                    $this->response = $callbacks[0]($this->response);
                    $this->exception = null;
                } catch (Exception $exception) {
                    $this->response = null;
                    $this->exception = $exception;
                }
            } elseif ($this->exception !== null) {
                try {
                    $this->response = $callbacks[1]($this->exception);
                    $this->exception = null;
                } catch (Exception $exception) {
                    $this->response = null;
                    $this->exception = $exception;
                }
            }
        }

        if ($this->response !== null) {
            $this->state = Promise::FULFILLED;

            if ($unwrap) {
                return $this->response;
            }
        }

        if ($this->exception !== null) {
            $this->state = Promise::REJECTED;

            if ($unwrap) {
                throw $this->exception;
            }
        }
    }
}
