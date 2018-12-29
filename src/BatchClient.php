<?php

declare(strict_types=1);

namespace Http\Client\Common;

use Http\Client\Exception;
use Http\Client\HttpClient;
use Http\Client\Common\Exception\BatchException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class BatchClient implements BatchClientInterface
{
    /**
     * @var HttpClient
     */
    private $client;

    public function __construct(HttpClient $client)
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
