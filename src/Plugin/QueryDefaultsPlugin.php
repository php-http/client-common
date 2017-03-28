<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;

/**
 * Set query to default value if it does not exist.
 *
 * If a given query parameter already exists the value wont be replaced and the request wont be changed.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class QueryDefaultsPlugin implements Plugin
{
    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @param array $queryParams Hashmap of query name to query value. Names and values must not be url encoded as
     *                           this plugin will encode them
     */
    public function __construct(array $queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        foreach ($this->queryParams as $name => $value) {
            $uri = $request->getUri();
            $array = [];
            parse_str($uri->getQuery(), $array);

            // If query value is not found
            if (!isset($array[$name])) {
                $array[$name] = $value;

                // Create a new request with the new URI with the added query param
                $request = $request->withUri(
                    $uri->withQuery(http_build_query($array))
                );
            }
        }

        return $next($request);
    }
}
