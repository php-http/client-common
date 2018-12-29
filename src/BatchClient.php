<?php

declare(strict_types=1);

namespace Http\Client\Common;

use Http\Client\Exception;
use Http\Client\Common\Exception\BatchException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class BatchClient implements BatchClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }

    public function sendRequests(array $requests): BatchResult
    {
        $batchResult = new BatchResult();

        foreach ($requests as $request) {
            try {
                $response = $this->sendRequest($request);
                $batchResult = $batchResult->addResponse($request, $response);
            } catch (Exception $e) {
                $batchResult = $batchResult->addException($request, $e);
            }
        }

        if ($batchResult->hasExceptions()) {
            throw new BatchException($batchResult);
        }

        return $batchResult;
    }
}
