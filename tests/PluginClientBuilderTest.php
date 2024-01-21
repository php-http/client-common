<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Client\Common\PluginClientBuilder;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PluginClientBuilderTest extends TestCase
{
    use ProphecyTrait;

    /** @dataProvider clientProvider */
    public function testPriority(string $client): void
    {
        $builder = new PluginClientBuilder();

        $plugins = [
            10 => $this->prophesize(Plugin::class)->reveal(),
            -10 => $this->prophesize(Plugin::class)->reveal(),
            0 => $this->prophesize(Plugin::class)->reveal(),
        ];

        foreach ($plugins as $priority => $plugin) {
            $builder->addPlugin($plugin, $priority);
        }

        $client = $this->prophesize($client)->reveal();
        $client = $builder->createClient($client);

        $closure = \Closure::bind(
            function (): array {
                return $this->plugins;
            },
            $client,
            PluginClient::class
        );

        $plugged = $closure();

        $expected = $plugins;
        krsort($expected);
        $expected = array_values($expected);

        $this->assertSame($expected, $plugged);
    }

    /** @dataProvider clientProvider */
    public function testOptions(string $client): void
    {
        $builder = new PluginClientBuilder();
        $builder->setOption('max_restarts', 5);

        $client = $this->prophesize($client)->reveal();
        $client = $builder->createClient($client);

        $closure = \Closure::bind(
            function (): array {
                return $this->options;
            },
            $client,
            PluginClient::class
        );

        $options = $closure();

        $this->assertArrayHasKey('max_restarts', $options);
        $this->assertSame(5, $options['max_restarts']);
    }

    public function clientProvider(): iterable
    {
        yield 'sync\'d http client' => [HttpClient::class];
        yield 'async\'d http client' => [HttpAsyncClient::class];
    }
}
