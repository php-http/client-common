<?php

declare(strict_types=1);

namespace Http\Client\Common;

use Http\Message\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpMethodsClient implements HttpMethodsClientInterface
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var RequestFactory|RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @param RequestFactory|RequestFactoryInterface
     */
    public function __construct(ClientInterface $httpClient, $requestFactory)
    {
        if (!$requestFactory instanceof RequestFactory && !$requestFactory instanceof RequestFactoryInterface) {
            throw new \TypeError(
                sprintf('%s::__construct(): Argument #2 ($requestFactory) must be of type %s|%s, %s given', self::class, RequestFactory::class, RequestFactoryInterface::class, get_debug_type($requestFactory))
            );
        }

        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    public function get($uri, array $headers = []): ResponseInterface
    {
        return $this->send('GET', $uri, $headers, null);
    }

    public function head($uri, array $headers = []): ResponseInterface
    {
        return $this->send('HEAD', $uri, $headers, null);
    }

    public function trace($uri, array $headers = []): ResponseInterface
    {
        return $this->send('TRACE', $uri, $headers, null);
    }

    public function post($uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->send('POST', $uri, $headers, $body);
    }

    public function put($uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->send('PUT', $uri, $headers, $body);
    }

    public function patch($uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->send('PATCH', $uri, $headers, $body);
    }

    public function delete($uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->send('DELETE', $uri, $headers, $body);
    }

    public function options($uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->send('OPTIONS', $uri, $headers, $body);
    }

    public function send(string $method, $uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->sendRequest($this->requestFactory->createRequest(
            $method,
            $uri,
            $headers,
            $body
        ));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }
}
