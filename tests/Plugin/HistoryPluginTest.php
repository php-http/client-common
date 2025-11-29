<?php

declare(strict_types=1);

namespace Tests\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin\HistoryPlugin;
use Http\Client\Common\Plugin\Journal;
use Http\Client\Exception\RequestException;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class HistoryPluginTest extends TestCase
{
    public function testRecordsSuccess(): void
    {
        $journal = $this->createMock(Journal::class);
        $plugin = new HistoryPlugin($journal);
        $request = new Request('GET', 'https://example.com');
        $response = new Response();

        $journal->expects($this->once())->method('addSuccess')->with($request, $response);

        $plugin->handleRequest($request, function () use ($response) {
            return new HttpFulfilledPromise($response);
        }, static function () {
        })->wait();
    }

    public function testRecordsFailure(): void
    {
        $journal = $this->createMock(Journal::class);
        $plugin = new HistoryPlugin($journal);
        $request = new Request('GET', 'https://example.com');
        $exception = new RequestException('error', $request);

        $journal->expects($this->once())->method('addFailure')->with($request, $exception);

        $promise = $plugin->handleRequest($request, function () use ($exception) {
            return new HttpRejectedPromise($exception);
        }, static function () {
        });

        $this->expectException(RequestException::class);
        $promise->wait();
    }
}
