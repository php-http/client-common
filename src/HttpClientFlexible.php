<?php

namespace Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;

/**
 * A flexible http client, which implements both interface and will emulate
 * one contract, the other, or none at all depending on the injected client contract.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class HttpClientFlexible implements HttpClient, HttpAsyncClient
{
    use HttpClientDecorator;
    use HttpAsyncClientDecorator;

    /**
     * @param HttpClient|HttpAsyncClient $client
     */
    public function __construct($client)
    {
        if (!($client instanceof HttpClient) && !($client instanceof HttpAsyncClient)) {
            throw new \LogicException('Client must be an instance of Http\\Client\\HttpClient or Http\\Client\\HttpAsyncClient');
        }

        $this->httpAsyncClient = $client;
        $this->httpClient = $client;

        if (!($this->httpAsyncClient instanceof HttpAsyncClient)) {
            $this->httpAsyncClient = new EmulatedHttpAsyncClient($this->httpAsyncClient);
        }

        if (!($this->httpClient instanceof HttpClient)) {
            $this->httpClient = new EmulatedHttpClient($this->httpClient);
        }
    }
}
