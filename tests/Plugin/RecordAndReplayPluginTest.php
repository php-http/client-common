<?php

declare(strict_types=1);

namespace tests\Http\Client\Common\Plugin;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use Http\Client\Common\Plugin\NamingStrategyInterface;
use Http\Client\Common\Plugin\RecordAndReplayPlugin;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use spec\Http\Client\Common\Plugin\PluginStub;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Gary PEGEOT <garypegeot@gmail.com>
 *
 * @internal
 */
final class RecordAndReplayPluginTest extends TestCase
{
    /**
     * @var NamingStrategyInterface|MockObject
     */
    private $strategy;

    private $directory;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var RecordAndReplayPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(NamingStrategyInterface::class);
        $this->directory = sys_get_temp_dir().\DIRECTORY_SEPARATOR.md5(random_bytes(10));
        $this->fs = new Filesystem();
        $this->plugin = new RecordAndReplayPlugin($this->strategy, $this->directory, $this->fs);
    }

    protected function tearDown(): void
    {
        if ($this->fs->exists($this->directory)) {
            $this->fs->remove($this->directory);
        }
    }

    public function testHandleRequest(): void
    {
        /** @var RequestInterface $request */
        $request = $this->createMock(RequestInterface::class);
        $next = function (): Promise {
            return new FulfilledPromise(new Response(200, ['X-Foo' => 'Bar'], '{"baz": true}'));
        };
        $filename = "$this->directory/foo.txt";
        $first = PluginStub::first();

        $this->assertFileNotExists($filename, 'File should not exists yet');

        $this->strategy->expects($this->any())->method('name')->with($request)->willReturn('foo');

        $response = $this->plugin->handleRequest($request, $next, $first)->wait();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFileExists($filename, 'File should be created');
        $this->assertStringEqualsFile($filename, str($response));

        $next = function (): void {
            $this->fail('Next should not be called when the fixture file exists');
        };
        $this->assertSame(str($response), str($this->plugin->handleRequest($request, $next, $first)->wait()));
    }
}
