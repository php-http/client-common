<?php

namespace Http\Client\Common;

use Http\Client\HttpClient;

/**
 * A builder that help you build a PluginClient.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PluginClientBuilder implements PluginClientBuilderInterface
{
    /**
     * @var Plugin[]
     */
    protected $plugins = [];

    /**
     * @var bool
     */
    protected $rebuildClient = true;

    /**
     * The last created client with the plugins
     *
     * @var PluginClient
     */
    private $pluginClient;

    /**
     * The object that sends HTTP messages
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Add a new plugin to the end of the plugin chain.
     *
     * @param Plugin $plugin
     */
    public function addPlugin(Plugin $plugin)
    {
        $this->plugins[] = $plugin;
        $this->rebuildClient = true;
    }

    /**
     * Remove a plugin by its fully qualified class name (FQCN).
     *
     * @param string $fqcn
     */
    public function removePlugin($fqcn)
    {
        foreach ($this->plugins as $idx => $plugin) {
            if ($plugin instanceof $fqcn) {
                unset($this->plugins[$idx]);
                $this->rebuildClient = true;
            }
        }
    }

    /**
     * Alias for removePlugin and addPlugin
     *
     * @param Plugin $plugin
     */
    public function replacePlugin(Plugin $plugin)
    {
        $this->removePlugin(get_class($plugin));
        $this->addPlugin($plugin);
    }

    /**
     * Get all plugins
     *
     * @return Plugin[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * This will overwrite all existing plugins
     *
     * @param Plugin[] $plugins
     *
     * @return PluginClientBuilder
     */
    public function setPlugins($plugins)
    {
        $this->plugins = $plugins;
        $this->rebuildClient = true;
    }

    /**
     * @return PluginClient
     */
    public function buildClient()
    {
        if (!$this->httpClient) {
            throw new \RuntimeException('No HTTP client were provided to the PluginBuilder.');
        }

        if ($this->rebuildClient) {
            $this->rebuildClient = false;
            $this->pushBackCachePlugin();

            $this->pluginClient = new PluginClient($this->httpClient, $this->plugins);
        }

        return $this->pluginClient;
    }

    /**
     * @return boolean
     */
    public function isModified()
    {
        return $this->rebuildClient;
    }

    /**
     * @param HttpClient $httpClient
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->rebuildClient = true;
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Make sure to move the cache plugin to the end of the chain
     */
    private function pushBackCachePlugin()
    {
        $cachePlugin = null;
        foreach ($this->plugins as $i => $plugin) {
            if ($plugin instanceof Plugin\CachePlugin) {
                $cachePlugin = $plugin;
                unset($this->plugins[$i]);

                $this->plugins[] = $cachePlugin;

                return;
            }
        }
    }
}
