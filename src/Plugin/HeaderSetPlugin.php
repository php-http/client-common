<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;

/**
 * Set headers to the request.
 * If the header does not exist it wil be set, if the header already exists it will be replaced.
 *
 * @author Soufiane Ghzal <sghzal@gmail.com>
 */
final class HeaderSetPlugin implements Plugin
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @param array $headers headers to set to the request
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        foreach ($this->headers as $header => $headerValue) {
            $request = $request->withHeader($header, $headerValue);
        }

        return $next($request);
    }
}
