<?php

declare(strict_types=1);

namespace Http\Client\Common\Plugin;

use Psr\Http\Message\RequestInterface;

/**
 * Provides a unique name to identify a request.
 *
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
interface NamingStrategyInterface
{
    public function name(RequestInterface $request): string;
}
