<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\HttpAsyncClientDecorator;
use Http\Client\Common\HttpClientDecorator;
use Http\Client\Common\FlexibleHttpClient;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Prophet;

class FlexibleHttpClientSpec extends ObjectBehavior
{
    function let(HttpClient $httpClient)
    {
        $this->beAnInstanceOf(
            'spec\Http\Client\Common\FlexibleHttpClientStub', [
                $httpClient
            ]
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\FlexibleHttpClient');
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
        $this->beConstructedWith($httpClient);

        $this->shouldThrow('\LogicException')->duringInstantiation();
    }

    function it_emulates_an_async_client(HttpClient $httpClient)
    {
        $this->beConstructedWith($httpClient);

        $this->getClient()->shouldImplement('Http\Client\HttpClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\HttpAsyncClient');
        $this->getClient()->shouldNotImplement('Http\Client\Common\EmulatedHttpClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\Common\EmulatedHttpAsyncClient');
    }

    function it_emulates_a_client(HttpAsyncClient $httpAsyncClient)
    {
        $this->beConstructedWith($httpAsyncClient);

        $this->getClient()->shouldImplement('Http\Client\HttpClient');
        $this->getAsyncClient()->shouldImplement('Http\Client\HttpAsyncClient');
        $this->getClient()->shouldImplement('Http\Client\Common\EmulatedHttpClient');
        $this->getAsyncClient()->shouldNotImplement('Http\Client\EmulatedHttpAsyncClient');
    }

    function it_does_not_emulates_a_client()
    {
        $prophet = new Prophet();
        $httpClient = $prophet->prophesize();
        $httpClient->willImplement('Http\Client\HttpClient');
        $httpClient->willImplement('Http\Client\HttpAsyncClient');

        $this->beConstructedWith($httpClient);

        $this->getClient()->shouldNotImplement('Http\Client\Common\EmulatedHttpClient');
        $this->getAsyncClient()->shouldNotImplement('Http\Client\Common\EmulatedHttpAsyncClient');
    }
}

class FlexibleHttpClientStub extends FlexibleHttpClient
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
