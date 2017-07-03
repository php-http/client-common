<?php

namespace Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;

/**
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 */
final class PluginClientFactory
{
    /**
     * @var callable
     */
    private static $factory;

    /**
     * Set the factory to use.
     * The callable to provide must have the same arguments and return type as PluginClientFactory::createClient.
     * This is used by the HTTPlugBundle to provide a better Symfony integration.
     *
     * @internal
     *
     * @param callable $factory
     */
    public static function setFactory(callable $factory)
    {
        static::$factory = $factory;
    }

    /**
     * @param HttpClient|HttpAsyncClient $client
     * @param Plugin[]                   $plugins
     * @param array                      $options {
     *
     *     @var string $client_name to give client a name which may be used when displaying client information  like in
     *         the HTTPlugBundle profiler.
     * }
     *
     * @see PluginClient constructor for PluginClient specific $options.
     *
     * @return PluginClient
     */
    public function createClient($client, array $plugins = [], array $options = [])
    {
        if (static::$factory) {
            $factory = static::$factory;

            return $factory($client, $plugins, $options);
        }

        return new PluginClient($client, $plugins, $options);
    }
}
