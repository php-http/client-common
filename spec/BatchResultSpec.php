<?php

namespace spec\Http\Client\Common;

use Http\Client\Common\BatchResult;
use Http\Client\Exception;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BatchResultSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beAnInstanceOf(BatchResult::class);
    }

    public function it_is_immutable(RequestInterface $request, ResponseInterface $response)
    {
        $new = $this->addResponse($request, $response);

        $this->getResponses()->shouldReturn([]);
        $new->shouldHaveType(BatchResult::class);
        $new->getResponses()->shouldReturn([$response]);
    }

    public function it_has_a_responses(RequestInterface $request, ResponseInterface $response)
    {
        $new = $this->addResponse($request, $response);

        $this->hasResponses()->shouldReturn(false);
        $this->getResponses()->shouldReturn([]);
        $new->hasResponses()->shouldReturn(true);
        $new->getResponses()->shouldReturn([$response]);
    }

    public function it_has_a_response_for_a_request(RequestInterface $request, ResponseInterface $response)
    {
        $new = $this->addResponse($request, $response);

        $this->shouldThrow(\UnexpectedValueException::class)->duringGetResponseFor($request);
        $this->isSuccessful($request)->shouldReturn(false);
        $new->getResponseFor($request)->shouldReturn($response);
        $new->isSuccessful($request)->shouldReturn(true);
    }

    public function it_keeps_exception_after_add_request(RequestInterface $request1, Exception $exception, RequestInterface $request2, ResponseInterface $response)
    {
        $new = $this->addException($request1, $exception);
        $new = $new->addResponse($request2, $response);

        $new->isSuccessful($request2)->shouldReturn(true);
        $new->isFailed($request2)->shouldReturn(false);
        $new->getResponseFor($request2)->shouldReturn($response);
        $new->isSuccessful($request1)->shouldReturn(false);
        $new->isFailed($request1)->shouldReturn(true);
        $new->getExceptionFor($request1)->shouldReturn($exception);
    }
}
