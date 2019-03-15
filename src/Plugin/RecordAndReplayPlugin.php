<?php

declare(strict_types=1);

namespace Http\Client\Common\Plugin;

use GuzzleHttp\Psr7;
use Http\Client\Common\Plugin;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Record successful responses into the filesystem and replay the response when a similar request is performed (VCR-like).
 *
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
final class RecordAndReplayPlugin implements Plugin
{
    /**
     * Return a unique name to identify a given request.
     *
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * The directory containing your fixtures (Must be writable).
     *
     * @var string
     */
    private $directory;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(NamingStrategyInterface $namingStrategy, string $directory, ?Filesystem $fs = null)
    {
        $this->namingStrategy = $namingStrategy;
        $this->directory = $directory;
        $this->fs = $fs ?? new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        if (!$this->fs->exists($this->directory)) {
            $this->fs->mkdir($this->directory);
        }

        $directory = realpath($this->directory);
        $name = $this->namingStrategy->name($request);
        $filename = "$directory/$name.txt";

        if ($this->fs->exists($filename)) {
            return new FulfilledPromise(Psr7\parse_response(file_get_contents($filename)));
        }

        return $next($request)->then(function (ResponseInterface $response) use ($filename) {
            if ($response->getStatusCode() < 300) {
                $this->fs->dumpFile($filename, Psr7\str($response));
            }

            return $response;
        });
    }
}
