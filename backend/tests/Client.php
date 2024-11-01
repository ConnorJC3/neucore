<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client extends \GuzzleHttp\Client
{
    /**
     * @var ResponseInterface[]
     */
    private array $responses = [];

    private array $middleware = [];

    public function setMiddleware(callable ...$middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function setResponse(ResponseInterface ...$responses): self
    {
        $this->responses = $responses;

        return $this;
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $response = array_shift($this->responses);
        if (!$response) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new \Exception('Missing Response');
        }
        $this->callMiddleware($request, $response);
        return $response;
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        return $this->send(new Request($method, $uri), $options);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->send($request);
    }

    private function callMiddleware(RequestInterface $request, ResponseInterface $response): void
    {
        foreach ($this->middleware as $callable) {
            $promise = new class($response) {
                private ResponseInterface $response;
                public function __construct(ResponseInterface $response)
                {
                    $this->response = $response;
                }
                public function then(callable $onFulfilled): void
                {
                    $onFulfilled($this->response);
                }
            };

            $function = $callable(function () use ($promise) {
                return $promise;
            });

            $function($request, []);
        }
    }
}
