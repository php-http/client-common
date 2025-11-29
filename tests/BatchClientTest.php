<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\BatchClient;
use Http\Client\Common\BatchResult;
use Http\Client\Common\Exception\BatchException;
use Http\Client\Exception\HttpException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BatchClientTest extends TestCase
{
    public function testSendRequestsReturnsBatchResult(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $request1 = $this->createMock(RequestInterface::class);
        $request2 = $this->createMock(RequestInterface::class);
        $response1 = $this->createMock(ResponseInterface::class);
        $response2 = $this->createMock(ResponseInterface::class);

        $client
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnMap([
                [$request1, $response1],
                [$request2, $response2],
            ]);

        $result = (new BatchClient($client))->sendRequests([$request1, $request2]);

        $this->assertTrue($result->isSuccessful($request1));
        $this->assertTrue($result->isSuccessful($request2));
    }

    public function testThrowsBatchExceptionWhenAnyRequestFails(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $request1 = $this->createMock(RequestInterface::class);
        $request2 = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $exception = new HttpException('failed', $request2, $response);

        $client
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($response, $this->throwException($exception));

        $this->expectException(BatchException::class);
        try {
            (new BatchClient($client))->sendRequests([$request1, $request2]);
        } catch (BatchException $e) {
            $this->assertInstanceOf(BatchResult::class, $e->getResult());

            throw $e;
        }
    }
}
