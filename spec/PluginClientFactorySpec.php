<?php

namespace spec\Http\Client\Common;

use Http\Client\HttpClient;
use PhpSpec\ObjectBehavior;

class PluginClientFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\PluginClientFactory');
    }

    function it_returns_a_plugin_client(HttpClient $httpClient)
    {
        $client = $this->createClient($httpClient);

        $client->shouldHaveType('Http\Client\Common\PluginClient');
    }
}
