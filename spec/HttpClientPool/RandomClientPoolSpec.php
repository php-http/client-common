<?php

namespace spec\Http\Client\Common\HttpClientPool;

use Http\Client\Common\HttpClientPoolItem;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Promise\Promise;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RandomClientPoolSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\HttpClientPool\RandomClientPool');
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    public function it_throw_exception_with_no_client(RequestInterface $request)
    {
        $this->shouldThrow('Http\Client\Common\Exception\HttpClientNotFoundException')->duringSendRequest($request);
        $this->shouldThrow('Http\Client\Common\Exception\HttpClientNotFoundException')->duringSendAsyncRequest($request);
    }

    public function it_sends_request(HttpClient $httpClient, RequestInterface $request, ResponseInterface $response)
    {
        $this->addHttpClient($httpClient);
        $httpClient->sendRequest($request)->willReturn($response);

        $this->sendRequest($request)->shouldReturn($response);
    }

    public function it_sends_async_request(HttpAsyncClient $httpAsyncClient, RequestInterface $request, Promise $promise)
    {
        $this->addHttpClient($httpAsyncClient);
        $httpAsyncClient->sendAsyncRequest($request)->willReturn($promise);
        $promise->then(Argument::type('callable'), Argument::type('callable'))->willReturn($promise);

        $this->sendAsyncRequest($request)->shouldReturn($promise);
    }

    public function it_throw_exception_if_no_more_enable_client(HttpClient $client, RequestInterface $request)
    {
        $this->addHttpClient($client);
        $client->sendRequest($request)->willThrow('Http\Client\Exception\HttpException');

        $this->shouldThrow('Http\Client\Exception\HttpException')->duringSendRequest($request);
        $this->shouldThrow('Http\Client\Common\Exception\HttpClientNotFoundException')->duringSendRequest($request);
    }

    public function it_reenable_client(HttpClient $client, RequestInterface $request)
    {
        $this->addHttpClient(new HttpClientPoolItem($client->getWrappedObject(), 0));
        $client->sendRequest($request)->willThrow('Http\Client\Exception\HttpException');

        $this->shouldThrow('Http\Client\Exception\HttpException')->duringSendRequest($request);
        $this->shouldThrow('Http\Client\Exception\HttpException')->duringSendRequest($request);
    }
}
