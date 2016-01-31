<?php

namespace Http\Client\Common;
use Psr\Http\Message\RequestInterface;

/**
 * RequestMatcher allow to tell if a PSR7 Request matches a specific strategy
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
interface RequestMatcher
{
    /**
     * Whether the request matches a specific strategy
     *
     * @param RequestInterface $request
     *
     * @return boolean
     */
    public function matches(RequestInterface $request);
}
