<?php

namespace Tests;

use GuzzleHttp\Client;
use App\Helpers\DebugTrace;
use Illuminate\Support\Arr;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Http\Response as HttpResponse;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Contracts\Foundation\Application;

/**
 * Trait MockGuzzleClient
 *
 * @package Tests
 * @mixin TestCase
 */
trait MockGuzzleClient
{
    /**
     * @var RequestInterface[]
     */
    protected $guzzleRequestLog = [];

    /**
     * @var MockHandler
     */
    protected $guzzleHandler;

    /**
     * @var bool
     */
    protected $traceTransaction = false;

    /**
     * @return $this
     */
    public function mockGuzzleResponses()
    {
        $this->guzzleHandler = app(MockHandler::class);

        $this->app->bind(Client::class, function (Application $app, array $args = []) {
            $handler = function (RequestInterface $request, array $options) {
                $mockResponse = $this->arrayToResponse(['body' => 'dummy-response']);
                !$this->guzzleHandler->count() && $this->guzzleHandler->append($mockResponse);
                $promise = ($this->guzzleHandler)($request, $options);
                $response = $promise->wait();
                $transaction = [
                    'request' => $this->requestToArray($request),
                    'response' => $this->responseToArray($response)
                ];
                $this->traceTransaction && $transaction['trace'] = app(DebugTrace::class)->generate()->truncate()['trace'];
                $this->guzzleRequestLog[] = $transaction;

                return $promise;
            };

            $handlerStack = HandlerStack::create($handler);
            $config = Arr::first($args, null, []);
            $config['handler'] = $handlerStack;

            return new Client($config);
        });

        return $this;
    }

    /**
     * @param bool $traceTransaction
     * @return $this
     */
    public function setTraceTransaction(bool $traceTransaction): self
    {
        $this->traceTransaction = $traceTransaction;

        return $this;
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    public function requestToArray(RequestInterface $request)
    {
        $method = $request->getMethod();
        $uri = (string)$request->getUri();
        parse_str((string)$request->getBody(), $body);
        $request->getBody()->rewind();
        $headers = array_map(function (array $header) {
            return count($header) == 1 ? current($header) : $header;
        }, $request->getHeaders());

        return compact('method', 'uri', 'headers', 'body');
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function responseToArray(ResponseInterface $response)
    {
        $status = $response->getStatusCode();
        parse_str((string)$response->getBody(), $body);
        $body = (count($body) == 1 && !current($body)) ? $body = key($body) : $body;
        $response->getBody()->rewind();
        $headers = array_map(function (array $header) {
            return count($header) == 1 ? current($header) : $header;
        }, $response->getHeaders());

        return compact('status', 'body', 'headers');
    }

    /**
     * @param array $request
     * @return RequestInterface
     */
    public function arrayToRequest(array $request)
    {
        $defaults = [
            'method' => 'GET',
            'uri' => '/',
            'headers' => [],
            'body' => null
        ];

        $request = array_merge($defaults, $request);
        $request = array_intersect_key($request, $defaults);

        if ($request['body']) {
            $request['body'] = is_array($request['body']) ? http_build_query($request['body']) : $request['body'];
            $request['body'] = stream_for($request['body']);
        }

        return app(GuzzleRequest::class, $request);
    }

    /**
     * @param array $response
     * @return ResponseInterface
     */
    public function arrayToResponse(array $response)
    {
        $defaults = [
            'status' => HttpResponse::HTTP_OK,
            'headers' => [],
            'body' => null
        ];

        $response = array_merge($defaults, $response);
        $response = array_intersect_key($response, $defaults);

        if ($response['body']) {
            $response['body'] = is_array($response['body']) ? http_build_query($response['body']) : $response['body'];
            $response['body'] = stream_for($response['body']);
        }

        return app(GuzzleResponse::class, $response);
    }
}
