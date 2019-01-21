<?php

declare(strict_types=1);

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Message\Stream\BufferedStream;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Decorate the body of the request and the response if it's not seekable by using Http\Message\Stream\BufferedStream.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class AlwaysSeekableBodyPlugin implements Plugin
{
    private $useFileBuffer;

    private $memoryBufferSize;

    /**
     * @param array $config {
     *
     *    @var bool $use_file_buffer    Whether this plugin should use a file as a buffer if the stream is too big, defaults to true
     *    @var int  $memory_buffer_size Max memory size in bytes to use for the buffer before it use a file, defaults to 2097152 (2 mb)
     * }
     */
    public function __construct(array $config = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'use_file_buffer' => true,
            'memory_buffer_size' => 2097152,
        ]);
        $resolver->setAllowedTypes('use_file_buffer', 'bool');
        $resolver->setAllowedTypes('memory_buffer_size', 'int');

        $options = $resolver->resolve($config);

        $this->useFileBuffer = $options['use_file_buffer'];
        $this->memoryBufferSize = $options['memory_buffer_size'];
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        if (!$request->getBody()->isSeekable()) {
            $request = $request->withBody(new BufferedStream($request->getBody(), $this->useFileBuffer, $this->memoryBufferSize));
        }

        return $next($request)->then(function (ResponseInterface $response) {
            if ($response->getBody()->isSeekable()) {
                return $response;
            }

            return $response->withBody(new BufferedStream($response->getBody(), $this->useFileBuffer, $this->memoryBufferSize));
        });
    }
}
