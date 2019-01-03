<?php

namespace spec\Http\Client\Common\Exception;

use Http\Client\Common\BatchResult;
use Http\Client\Exception;
use PhpSpec\ObjectBehavior;
use Http\Client\Common\Exception\BatchException;

class BatchExceptionSpec extends ObjectBehavior
{
    public function let()
    {
        $batchResult = new BatchResult();
        $this->beConstructedWith($batchResult);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BatchException::class);
    }

    public function it_is_a_runtime_exception()
    {
        $this->shouldHaveType(\RuntimeException::class);
    }

    public function it_is_an_exception()
    {
        $this->shouldImplement(Exception::class);
    }

    public function it_has_a_batch_result()
    {
        $this->getResult()->shouldHaveType(BatchResult::class);
    }
}
