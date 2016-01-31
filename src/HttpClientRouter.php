<?php

namespace Http\Client\Common;

use Http\Client\Exception\RequestException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

/**
 *
 */
class HttpClientRouter implements HttpClient, HttpAsyncClient
{
    private $clients = [];

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $client = $this->chooseHttpClient($request);

        return $client->sendAsyncRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $client = $this->chooseHttpClient($request);

        return $client->sendRequest($request);
    }

    /**
     * Add a client to the router
     *
     * @param HttpClient|HttpAsyncClient $client
     * @param RequestMatcher             $requestMatcher
     */
    public function addClient($client, RequestMatcher $requestMatcher)
    {
        $this->clients[] = [
            'matcher' => $requestMatcher,
            'client'  => new HttpClientFlexible($client)
        ];
    }

    /**
     * Choose a http client given a specific request
     *
     * @param RequestInterface $request
     *
     * @return HttpClient|HttpAsyncClient
     */
    protected function chooseHttpClient(RequestInterface $request)
    {
        foreach ($this->clients as $client) {
            if ($client['matcher']->matches($request)) {
                return $client['client'];
            }
        }

        throw new RequestException('No client found for the specified request', $request);
    }
}
