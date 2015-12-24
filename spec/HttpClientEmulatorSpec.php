<?php

namespace spec\Http\Client\Common;

use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Http\Client\Common\HttpClientEmulator;
use Http\Client\Common\HttpAsyncClientDecorator;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class HttpClientEmulatorSpec extends ObjectBehavior
{
    function let(HttpAsyncClient $httpAsyncClient)
    {
        $this->beAnInstanceOf('spec\Http\Client\Common\HttpClientEmulatorStub', [$httpAsyncClient]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('spec\Http\Client\Common\HttpClientEmulatorStub');
    }

    function it_emulates_a_successful_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise, ResponseInterface $response)
    {
        $promise->wait()->shouldBeCalled();
        $promise->getState()->willReturn(Promise::FULFILLED);
        $promise->wait()->willReturn($response);

        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->sendRequest($request)->shouldReturn($response);
    }

    function it_emulates_a_failed_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $promise->wait()->shouldBeCalled();
        $promise->getState()->willReturn(Promise::REJECTED);
        $promise->wait()->willThrow(new TransferException());

        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);

        $this->shouldThrow('Http\Client\Exception')->duringSendRequest($request);
    }
}

class HttpClientEmulatorStub implements HttpAsyncClient, HttpClient
{
    use HttpAsyncClientDecorator;
    use HttpClientEmulator;

    /**
     * @param HttpAsyncClient $httpAsyncClient
     */
    public function __construct(HttpAsyncClient $httpAsyncClient)
    {
        $this->httpAsyncClient = $httpAsyncClient;
    }
}
