<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\PluginClient;
use Http\Client\Common\PluginClientFactory;
use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;

class PluginClientFactoryTest extends TestCase
{
    public function testReturnsPluginClient(): void
    {
        $factory = new PluginClientFactory();
        $client = $this->createMock(HttpClient::class);

        $this->assertInstanceOf(PluginClient::class, $factory->createClient($client));
    }

    public function testAcceptsClientNameOption(): void
    {
        $factory = new PluginClientFactory();
        $client = $this->createMock(HttpClient::class);

        $factory->createClient($client, [], ['client_name' => 'Default']);
        $this->addToAssertionCount(1);
    }
}
