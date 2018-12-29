<?php

namespace tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class AddPathPluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var callable empty
     */
    private $first;

    protected function setUp()
    {
        $this->first = function () {};
        $this->plugin = new AddPathPlugin(new Uri('/api'));
    }

    public function testRewriteSameUrl()
    {
        $verify = function (RequestInterface $request) {
            $this->assertEquals('https://example.com/api/foo', $request->getUri()->__toString());
        };

        $request = new Request('GET', 'https://example.com/foo', ['Content-Type'=>'text/html']);
        $this->plugin->handleRequest($request, $verify, $this->first);

        // Make a second call with the same $request object
        $this->plugin->handleRequest($request, $verify, $this->first);

        // Make a new call with a new object but same URL
        $request = new Request('GET', 'https://example.com/foo', ['Content-Type'=>'text/plain']);
        $this->plugin->handleRequest($request, $verify, $this->first);
    }

    public function testRewriteCallingThePluginTwice()
    {
        $request = new Request('GET', 'https://example.com/foo');
        $this->plugin->handleRequest($request, function (RequestInterface $request) {
            $this->assertEquals('https://example.com/api/foo', $request->getUri()->__toString());

            // Run the plugin again with the modified request
            $this->plugin->handleRequest($request, function (RequestInterface $request) {
                $this->assertEquals('https://example.com/api/foo', $request->getUri()->__toString());
            }, $this->first);
        }, $this->first);
    }

    public function testRewriteWithDifferentUrl()
    {
        $request = new Request('GET', 'https://example.com/foo');
        $this->plugin->handleRequest($request, function (RequestInterface $request) {
            $this->assertEquals('https://example.com/api/foo', $request->getUri()->__toString());
        }, $this->first);

        $request = new Request('GET', 'https://example.com/bar');
        $this->plugin->handleRequest($request, function (RequestInterface $request) {
            $this->assertEquals('https://example.com/api/bar', $request->getUri()->__toString());
        }, $this->first);
    }

    public function testRewriteWhenPathIsIncluded()
    {
        $verify = function (RequestInterface $request) {
            $this->assertEquals('https://example.com/api/foo', $request->getUri()->__toString());
        };

        $request = new Request('GET', 'https://example.com/api/foo');
        $this->plugin->handleRequest($request, $verify, $this->first);
    }
}
