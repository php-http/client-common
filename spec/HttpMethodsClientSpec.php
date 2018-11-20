<?php

namespace spec\Http\Client\Common;

use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Message\MessageFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;

class HttpMethodsClientSpec extends ObjectBehavior
{
    function let(HttpClient $client, MessageFactory $messageFactory)
    {
        $this->beAnInstanceOf(
            HttpMethodsClientStub::class, [
                $client,
                $messageFactory
            ]
        );
    }

    function it_sends_a_get_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->get($data['uri'], $data['headers'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_head_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->head($data['uri'], $data['headers'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_trace_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->trace($data['uri'], $data['headers'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_post_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->post($data['uri'], $data['headers'], $data['body'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_put_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->put($data['uri'], $data['headers'], $data['body'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_patch_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->patch($data['uri'], $data['headers'], $data['body'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_delete_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->delete($data['uri'], $data['headers'], $data['body'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_a_options_request()
    {
        $data = HttpMethodsClientStub::$requestData;

        $this->options($data['uri'], $data['headers'], $data['body'])->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    function it_sends_request_with_underlying_client(HttpClient $client, MessageFactory $messageFactory, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->shouldBeCalled()->willReturn($response);

        $this->beConstructedWith($client, $messageFactory);
        $this->sendRequest($request)->shouldReturn($response);
    }
}

class HttpMethodsClientStub extends HttpMethodsClient
{
    public static $requestData = [
        'uri'     => '/uri',
        'headers' => [
            'Content-Type' => 'text/plain',
        ],
        'body'    => 'body'
    ];

    /**
     * {@inheritdoc}
     */
    public function send($method, $uri, array $headers = [], $body = null): ResponseInterface
    {
        if ($uri !== self::$requestData['uri']) {
            throw new \InvalidArgumentException('Invalid URI: ' . $uri);
        }

        if ($headers !== self::$requestData['headers']) {
            throw new \InvalidArgumentException('Invalid headers: ' . print_r($headers, true));
        }

        switch ($method) {
            case 'GET':
            case 'HEAD':
            case 'TRACE':
                if ($body !== null) {
                    throw new \InvalidArgumentException('Non-empty body');
                }

                return new Response();
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
            case 'OPTIONS':
                if ($body !== self::$requestData['body']) {
                    throw new \InvalidArgumentException('Invalid body: ' . print_r($body, true));
                }

                return new Response();
            default:
                throw new \InvalidArgumentException('Invalid method: ' . $method);
        }
    }
}
