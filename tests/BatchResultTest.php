<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common;

use Http\Client\Common\BatchResult;
use Http\Client\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BatchResultTest extends TestCase
{
    public function testAddResponseIsImmutable(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $result = new BatchResult();
        $newResult = $result->addResponse($request, $response);

        $this->assertFalse($result->hasResponses());
        $this->assertSame([], $result->getResponses());
        $this->assertTrue($newResult->hasResponses());
        $this->assertSame([$response], $newResult->getResponses());
    }

    public function testResponseLookups(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $result = (new BatchResult())->addResponse($request, $response);
        $this->assertTrue($result->isSuccessful($request));
        $this->assertSame($response, $result->getResponseFor($request));

        $this->expectException(\UnexpectedValueException::class);
        (new BatchResult())->getResponseFor($request);
    }

    public function testKeepsExceptionsWhenAddingResponses(): void
    {
        $requestFailed = $this->createMock(RequestInterface::class);
        $requestSucceeded = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $exception = $this->createMock(Exception::class);

        $result = (new BatchResult())
            ->addException($requestFailed, $exception)
            ->addResponse($requestSucceeded, $response);

        $this->assertTrue($result->isFailed($requestFailed));
        $this->assertSame($exception, $result->getExceptionFor($requestFailed));
        $this->assertFalse($result->isFailed($requestSucceeded));
        $this->assertTrue($result->isSuccessful($requestSucceeded));
        $this->assertSame($response, $result->getResponseFor($requestSucceeded));
    }
}
