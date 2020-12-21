<?php

namespace Tests;

use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;

trait MockGuzzleResponses
{
    /**
     * Creates queue of mock responses for the Guzzle client.
     * This method can return the requests history by reference.
     *
     * @param array $responses
     *
     * @return array
     */
    protected function &mockResponses(array $responses = [])
    {
        $requestsHistory = [];
        $queue = [];

        foreach ($responses as $response) {
            $status_code = isset($response['status_code']) ? $response['status_code'] : 200;
            $headers = isset($response['headers']) ? $response['headers'] : [];
            $body = isset($response['body']) ? $response['body'] : [];

            array_push($queue, new Response(
                $status_code,
                $headers + ['Content-Type' => 'application/json'],
                GuzzleHttp\json_encode($body)
            ));
        }

        $stack = HandlerStack::create(new MockHandler($queue));
        $stack->push(Middleware::history($requestsHistory));

        $this->app->instance('GuzzleHttp\Client', new Client(['handler' => $stack]));

        return $requestsHistory;
    }

    /**
     * Wrapper for mockResponses for use with a single response.
     *
     * @param array $response
     */
    protected function mockResponse(array $response = [])
    {
        $this->mockResponses([$response]);
    }
}
