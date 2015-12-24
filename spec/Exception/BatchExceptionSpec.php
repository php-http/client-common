<?php

namespace spec\Http\Client\Common\Exception;

use Http\Client\Common\BatchResult;
use Http\Client\Exception;
use PhpSpec\ObjectBehavior;

class BatchExceptionSpec extends ObjectBehavior
{
    function let()
    {
        $batchResult = new BatchResult();
        $this->beConstructedWith($batchResult);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Exception\BatchException');
    }

    function it_is_a_runtime_exception()
    {
        $this->shouldHaveType('RuntimeException');
    }

    function it_is_an_exception()
    {
        $this->shouldImplement('Http\Client\Exception');
    }

    function it_has_a_batch_result()
    {
        $this->getResult()->shouldHaveType('Http\Client\Common\BatchResult');
    }
}
