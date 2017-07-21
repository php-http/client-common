<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;

/**
 * Allow to set the correct content type header on the request automatically only if it is not set .
 *
 * @author Karim Pinchon <karim.pinchon@gmail.com>
 */
final class ContentTypePlugin implements Plugin
{
    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        if (!$request->hasHeader('Content-Type')) {
            $stream = $request->getBody();
            $streamSize = $stream->getSize();

            if (0 == $streamSize) {
                return $next($request);
            }

            if ($this->isJson($stream)) {
                $request = $request->withHeader('Content-Type', 'application/json');

                return $next($request);
            }

            if ($this->isXml($stream)) {
                $request = $request->withHeader('Content-Type', 'application/xml');

                return $next($request);
            }
        }

        return $next($request);
    }

    /**
     * @param $stream
     *
     * @return bool
     */
    private function isJson($stream)
    {
        json_decode($stream);

        return json_last_error() == JSON_ERROR_NONE;
    }

    /**
     * @param $stream
     *
     * @return \SimpleXMLElement|false
     */
    private function isXml($stream)
    {
        $previousValue = libxml_use_internal_errors(true);
        $isXml = simplexml_load_string($stream);
        libxml_use_internal_errors($previousValue);

        return $isXml;
    }
}
