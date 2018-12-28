<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A client that helps you migrate from php-http/httplug 1.x to 2.x. This
 * will also help you to support PHP5 at the same time you support 2.x.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class VersionBridgeClient implements HttpClient
{
    abstract public function doSendRequest(RequestInterface $request);

    public function sendRequest(RequestInterface $request)
    {
        return $this->doSendRequest($request);
    }
}
