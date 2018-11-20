<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Exception\TransferException;
use Http\Client\Common\Plugin\Journal;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Http\Client\Common\Plugin;

class HistoryPluginSpec extends ObjectBehavior
{
    public function let(Journal $journal)
    {
        $this->beConstructedWith($journal);
    }

    public function it_is_initializable()
    {
        $this->beAnInstanceOf('Http\Client\Common\Plugin\JournalPlugin');
    }

    public function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    public function it_records_success(Journal $journal, RequestInterface $request, ResponseInterface $response)
    {
        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpFulfilledPromise($response->getWrappedObject());
            }
        };

        $journal->addSuccess($request, $response)->shouldBeCalled();

        $this->handleRequest($request, $next, function () {});
    }

    public function it_records_failure(Journal $journal, RequestInterface $request)
    {
        $exception = new TransferException();
        $next = function (RequestInterface $receivedRequest) use ($request, $exception) {
            if (Argument::is($request->getWrappedObject())->scoreArgument($receivedRequest)) {
                return new HttpRejectedPromise($exception);
            }
        };

        $journal->addFailure($request, $exception)->shouldBeCalled();

        $this->handleRequest($request, $next, function () {});
    }
}
