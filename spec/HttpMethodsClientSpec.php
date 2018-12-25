<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpMethodsClientSpec extends ObjectBehavior
{
    private static $requestData = [
        'uri' => '/uri',
        'headers' => [
            'Content-Type' => 'text/plain',
        ],
        'body' => 'body',
    ];

    public function let(HttpClient $client, RequestFactory $requestFactory)
    {
        $this->beAnInstanceOf(
            HttpMethodsClient::class, [
                $client,
                $requestFactory,
            ]
        );
    }

    public function it_sends_a_get_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'get');
    }

    public function it_sends_a_head_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'head');
    }

    public function it_sends_a_trace_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'trace');
    }

    public function it_sends_a_post_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'post', self::$requestData['body']);
    }

    public function it_sends_a_put_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'put', self::$requestData['body']);
    }

    public function it_sends_a_patch_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'patch', self::$requestData['body']);
    }

    public function it_sends_a_delete_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'delete', self::$requestData['body']);
    }

    public function it_sends_an_options_request(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response)
    {
        $this->assert($client, $requestFactory, $request, $response, 'options', self::$requestData['body']);
    }

    /**
     * Run the actual test.
     *
     * As there is no data provider in phpspec, we keep separate methods to get new mocks for each test.
     */
    private function assert(HttpClient $client, RequestFactory $requestFactory, RequestInterface $request, ResponseInterface $response, string $method, string $body = null)
    {
        $client->sendRequest($request)->shouldBeCalled()->willReturn($response);
        $this->mockFactory($requestFactory, $request, strtoupper($method), $body);

        $this->$method(self::$requestData['uri'], self::$requestData['headers'], self::$requestData['body'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    private function mockFactory(RequestFactory $requestFactory, RequestInterface $request, string $method, string $body = null)
    {
        $requestFactory->createRequest($method, self::$requestData['uri'], self::$requestData['headers'], $body)->willReturn($request);
    }
}
