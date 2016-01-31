<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\HttpAsyncClientDecorator;
use Http\Client\Common\HttpClientDecorator;
use Http\Client\Common\HttpClientFlexible;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use PhpSpec\ObjectBehavior;

class HttpClientFlexibleSpec extends ObjectBehavior
{
    function let(HttpClient $httpClient)
    {
        $this->beAnInstanceOf(
            'spec\Http\Client\Common\HttpClientFlexibleStub', [
                $httpClient
            ]
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\HttpClientFlexible');
    }

    function it_is_an_http_client()
    {
        $this->shouldImplement('Http\Client\HttpClient');
    }

    function it_is_an_async_http_client()
    {
        $this->shouldImplement('Http\Client\HttpAsyncClient');
    }

    function it_throw_exception_if_invalid_client()
    {
        $httpClient = null;

        $this->shouldThrow('\LogicException')->during('__construct', [$httpClient]);
    }

    function it_emulates_an_async_client(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);

        $this->getClient()->shouldImplement('Http\Client\HttpClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\HttpAsyncClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\Common\EmulatedHttpAsyncClient');
    }

    function it_emulates_a_client(HttpAsyncClient $httpAsyncClient)
    {
        $this->beConstructedWith($httpAsyncClient);

        $this->getClient()->shouldImplement('Http\Client\HttpClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\HttpAsyncClient');
        $this->getClient()->shouldImplement('Http\Client\Common\EmulatedHttpClient');
    }

    function it_does_not_emulates_a_client(HttpClientFull $httpClient)
    {
        $this->beConstructedWith($httpClient);

        $this->getClient()->shouldImplement('Http\Client\HttpClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\HttpAsyncClient');
        $this->getClient()->shouldImplement('spec\Http\Client\Common\HttpClientFull');
        $this->getAsyncClient()->shouldImplement('spec\Http\Client\Common\HttpClientFull');
        $this->getClient()->shouldNotImplement('Http\Client\Common\EmulatedHttpClient');
        $this->getAsyncClient()->shouldNotImplement('Http\Client\Common\EmulatedHttpAsyncClient');
    }
}

class HttpClientFull implements HttpClient, HttpAsyncClient
{
    use HttpClientDecorator;
    use HttpAsyncClientDecorator;
}

class HttpClientFlexibleStub extends HttpClientFlexible
{
    public function getClient()
    {
        return $this->httpClient;
    }

    public function getAsyncClient()
    {
        return $this->httpAsyncClient;
    }
}
