<?php

namespace Http\Client\Common;

use Http\Client\HttpClient;

/**
 * A builder that help you build a PluginClient.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface PluginClientBuilderInterface
{
    /**
     * Add a new plugin to the end of the plugin chain.
     *
     * @param Plugin $plugin
     */
    public function addPlugin(Plugin $plugin);

    /**
     * Remove a plugin by its fully qualified class name (FQCN).
     *
     * @param string $fqcn
     */
    public function removePlugin($fqcn);

    /**
     * Alias for removePlugin and addPlugin.
     *
     * @param Plugin $plugin
     */
    public function replacePlugin(Plugin $plugin);

    /**
     * Get all plugins.
     *
     * @return Plugin[]
     */
    public function getPlugins();

    /**
     * This will overwrite all existing plugins.
     *
     * @param Plugin[] $plugins
     *
     * @return PluginClientBuilder
     */
    public function setPlugins($plugins);

    /**
     * @return PluginClient
     */
    public function buildClient();

    /**
     * @param HttpClient $httpClient
     */
    public function setHttpClient(HttpClient $httpClient);

    /**
     * @return HttpClient
     */
    public function getHttpClient();

    /**
     * @return bool
     */
    public function isModified();
}
