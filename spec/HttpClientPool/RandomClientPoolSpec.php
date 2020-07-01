<?php

namespace spec\Http\Client\Common\HttpClientPool;

use Http\Client\Common\Exception\HttpClientNotFoundException;
use Http\Client\Common\HttpClientPool\HttpClientPoolItem;
use Http\Client\Common\HttpClientPool\RandomClientPool;
use Http\Client\Exception\HttpException;
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
        $this->shouldHaveType(RandomClientPool::class);
    }

    public function it_is_an_http_client()
    {
        $this->shouldImplement(HttpClient::class);
    }

    public function it_is_an_async_http_client()
    {
        $this->shouldImplement(HttpAsyncClient::class);
    }

    public function it_throw_exception_with_no_client(RequestInterface $request)
    {
        $this->shouldThrow(HttpClientNotFoundException::class)->duringSendRequest($request);
        $this->shouldThrow(HttpClientNotFoundException::class)->duringSendAsyncRequest($request);
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
        $client->sendRequest($request)->willThrow(HttpException::class);

        $this->shouldThrow(HttpException::class)->duringSendRequest($request);
        $this->shouldThrow(HttpClientNotFoundException::class)->duringSendRequest($request);
    }

    public function it_reenable_client(HttpClient $client, RequestInterface $request)
    {
        $this->addHttpClient(new HttpClientPoolItem($client->getWrappedObject(), 0));
        $client->sendRequest($request)->willThrow(HttpException::class);

        $this->shouldThrow(HttpException::class)->duringSendRequest($request);
        $this->shouldThrow(HttpException::class)->duringSendRequest($request);
    }
}
