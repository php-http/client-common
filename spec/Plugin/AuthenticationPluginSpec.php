<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication;
use Http\Promise\Promise;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

class AuthenticationPluginSpec extends ObjectBehavior
{
    public function let(Authentication $authentication)
    {
        $this->beConstructedWith($authentication);
    }

    public function it_is_initializable(Authentication $authentication)
    {
        $this->shouldHaveType(AuthenticationPlugin::class);
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_sends_an_authenticated_request(Authentication $authentication, RequestInterface $notAuthedRequest, RequestInterface $authedRequest, Promise $promise)
    {
        $authentication->authenticate($notAuthedRequest)->willReturn($authedRequest);

        $next = function (RequestInterface $request) use ($authedRequest, $promise) {
            if (Argument::is($authedRequest->getWrappedObject())->scoreArgument($request)) {
                return $promise->getWrappedObject();
            }
        };

        $this->handleRequest($notAuthedRequest, $next, function () {})->shouldReturn($promise);
    }
}
