<?php

namespace spec\Http\Client\Common\Plugin;

use GuzzleHttp\Psr7\Response;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

class PluginStub
{
    public static function next(): callable
    {
        return function (RequestInterface $request): Promise
        {
            return new FulfilledPromise(new Response());
        };
    }

    public static function first(): callable
    {
        return function (RequestInterface $request): Promise
        {
            return new FulfilledPromise(new Response());
        };
    }
}
