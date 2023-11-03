<?php

declare(strict_types=1);

namespace Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Decorates an HTTP Async Client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait HttpAsyncClientDecorator
{
    /**
     * @var HttpAsyncClient
     */
    protected $httpAsyncClient;

    /**
     * @see HttpAsyncClient::sendAsyncRequest
     * @return Promise<ResponseInterface>
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        return $this->httpAsyncClient->sendAsyncRequest($request);
    }
}
