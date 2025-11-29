<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Exception;

use Http\Client\Common\BatchResult;
use Http\Client\Common\Exception\BatchException;
use Http\Client\Exception;
use PHPUnit\Framework\TestCase;

class BatchExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new BatchException(new BatchResult());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testStoresBatchResult(): void
    {
        $result = new BatchResult();
        $exception = new BatchException($result);

        $this->assertSame($result, $exception->getResult());
    }
}
