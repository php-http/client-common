<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\RequestMatcherPlugin;
use Http\Message\RequestMatcher;
use Http\Promise\Promise;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

class RequestMatcherPluginSpec extends ObjectBehavior
{
    public function let(RequestMatcher $requestMatcher, Plugin $plugin)
    {
        $this->beConstructedWith($requestMatcher, $plugin);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RequestMatcherPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_matches_a_request_and_delegates_to_plugin(
        RequestInterface $request,
        RequestMatcher $requestMatcher,
        Plugin $plugin
    ) {
        $requestMatcher->matches($request)->willReturn(true);
        $plugin->handleRequest($request, Argument::type('callable'), Argument::type('callable'))->shouldBeCalled();

        $this->handleRequest($request, PluginStub::next(), function () {});
    }

    public function it_does_not_match_a_request(
        RequestInterface $request,
        RequestMatcher $requestMatcher,
        Plugin $plugin,
        Promise $promise
    ) {
        $requestMatcher->matches($request)->willReturn(false);
        $plugin->handleRequest($request, Argument::type('callable'), Argument::type('callable'))->shouldNotBeCalled();

        $next = function (RequestInterface $request) use ($promise) {
            return $promise->getWrappedObject();
        };

        $this->handleRequest($request, $next, function () {})->shouldReturn($promise);
    }
}
