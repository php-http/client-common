<?php

namespace Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Http\Client\Exception;

/**
 * A HttpClientPoolItem represent a HttpClient inside a Pool.
 *
 * It is disabled when a request failed and can be reenable after a certain number of seconds
 * It also keep tracks of the current number of request the client is currently sending (only usable for async method)
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class HttpClientPoolItem implements HttpClient, HttpAsyncClient
{
    /** @var int Number of request this client is currently sending */
    private $sendingRequestCount = 0;

    /** @var bool Status of the http client */
    private $disabled = false;

    /** @var \DateTime Time when this client has been disabled */
    private $disabledAt;

    /** @var int|null Number of seconds after this client is reenable, by default null: never reenable this client */
    private $reenableAfter;

    /** @var FlexibleHttpClient A http client responding to async and sync request */
    private $client;

    /**
     * {@inheritdoc}
     *
     * @param null|int $reenableAfter Number of seconds after this client is reenable
     */
    public function __construct($client, $reenableAfter = null)
    {
        $this->client = new FlexibleHttpClient($client);
        $this->reenableAfter = $reenableAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        if ($this->isDisabled()) {
            throw new Exception\RequestException('Cannot send the request as this client has been disabled', $request);
        }

        try {
            ++$this->sendingRequestCount;
            $response = $this->client->sendRequest($request);
            --$this->sendingRequestCount;
        } catch (Exception $e) {
            $this->disable();
            --$this->sendingRequestCount;

            throw $e;
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        if ($this->isDisabled()) {
            throw new Exception\RequestException('Cannot send the request as this client has been disabled', $request);
        }

        ++$this->sendingRequestCount;

        return $this->client->sendAsyncRequest($request)->then(function ($response) {
            --$this->sendingRequestCount;

            return $response;
        }, function ($exception) {
            $this->disable();
            --$this->sendingRequestCount;

            throw $exception;
        });
    }

    /**
     * Get current number of request that is send by the underlying http client.
     *
     * @return int
     */
    public function getSendingRequestCount()
    {
        return $this->sendingRequestCount;
    }

    /**
     * Disable the current client.
     */
    protected function disable()
    {
        $this->disabled = true;
        $this->disabledAt = new \DateTime('now');
    }

    /**
     * Whether this client is disabled or not.
     *
     * Will also reactivate this client if possible
     *
     * @return bool
     */
    public function isDisabled()
    {
        if ($this->disabled && null !== $this->reenableAfter) {
            // Reenable after a certain time
            $now = new \DateTime();

            if (($now->getTimestamp() - $this->disabledAt->getTimestamp()) >= $this->reenableAfter) {
                $this->disabled = false;
            }
        }

        return $this->disabled;
    }
}
