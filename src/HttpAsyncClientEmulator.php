<?php

namespace Http\Client\Common;

use Http\Client\Exception;
use Http\Promise;
use Psr\Http\Message\RequestInterface;

/**
 * Emulates an HTTP Async Client in an HTTP Client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait HttpAsyncClientEmulator
{
    /**
     * {@inheritdoc}
     *
     * @see HttpClient::sendRequest
     */
    abstract public function sendRequest(RequestInterface $request);

    /**
     * {@inheritdoc}
     *
     * @see HttpAsyncClient::sendAsyncRequest
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        try {
            return new Promise\FulfilledPromise($this->sendRequest($request));
        } catch (Exception $e) {
            return new Promise\RejectedPromise($e);
        }
    }
}
