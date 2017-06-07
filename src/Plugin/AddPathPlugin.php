<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Prepend a base path to the request URI. Useful for base API URLs like http://domain.com/api.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class AddPathPlugin implements Plugin
{
    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var bool
     */
    private $alwaysPrepend;

    /**
     * @param UriInterface $uri
     * @param array        $config {
     *
     *     @var bool $alwaysPrepend Set to true to always prepend the path even if the request path start with that path.
     * }
     */
    public function __construct(UriInterface $uri, array $config = [])
    {
        if ($uri->getPath() === '') {
            throw new \LogicException('URI path cannot be empty');
        }

        if (substr($uri->getPath(), -1) === '/') {
            throw new \LogicException('URI path cannot end with a slash.');
        }

        $this->uri = $uri;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($config);

        $this->alwaysPrepend = $options['always_prepend'];
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $prepend = $this->uri->getPath();
        $path = $request->getUri()->getPath();

        if ($this->alwaysPrepend || substr($path, 0, strlen($prepend)) !== $prepend) {
            $request = $request->withUri($request->getUri()
                ->withPath($prepend.$path)
            );
        }

        return $next($request);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'always_prepend' => true,
        ]);
        $resolver->setAllowedTypes('always_prepend', 'bool');
    }
}
