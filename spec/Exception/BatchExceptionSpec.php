<?php

namespace spec\Http\Client\Common\Exception;

use Http\Client\Common\BatchResult;
use PhpSpec\ObjectBehavior;

class BatchExceptionSpec extends ObjectBehavior
{
    public function let()
    {
        $batchResult = new BatchResult();
        $this->beConstructedWith($batchResult);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Exception\BatchException');
    }

    public function it_is_a_runtime_exception()
    {
        $this->shouldHaveType('RuntimeException');
    }

    public function it_is_an_exception()
    {
        $this->shouldImplement('Http\Client\Exception');
    }

    public function it_has_a_batch_result()
    {
        $this->getResult()->shouldHaveType('Http\Client\Common\BatchResult');
    }
}
