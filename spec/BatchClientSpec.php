<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\BatchClient;
use Http\Client\Common\BatchResult;
use Http\Client\Common\Exception\BatchException;
use Http\Client\Exception\HttpException;
use Http\Client\HttpClient;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BatchClientSpec extends ObjectBehavior
{
    public function let(HttpClient $client)
    {
        $this->beAnInstanceOf(BatchClient::class, [$client]);
    }

    public function it_send_multiple_request_using_send_request(HttpClient $client, RequestInterface $request1, RequestInterface $request2, ResponseInterface $response1, ResponseInterface $response2)
    {
        $client->sendRequest($request1)->willReturn($response1);
        $client->sendRequest($request2)->willReturn($response2);

        $this->sendRequests([$request1, $request2])->shouldReturnAnInstanceOf(BatchResult::class);
    }

    public function it_throw_batch_exception_if_one_or_more_request_failed(HttpClient $client, RequestInterface $request1, RequestInterface $request2, ResponseInterface $response)
    {
        $client->sendRequest($request1)->willReturn($response);
        $client->sendRequest($request2)->willThrow(HttpException::class);

        $this->shouldThrow(BatchException::class)->duringSendRequests([$request1, $request2]);
    }
}
