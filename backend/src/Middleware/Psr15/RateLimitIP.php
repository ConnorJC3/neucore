<?php

declare(strict_types=1);

namespace Neucore\Middleware\Psr15;

use Neucore\Service\Config;
use Neucore\Storage\ApcuStorage;
use Neucore\Storage\StorageInterface;
use Neucore\Storage\Variables;
use Neucore\Util\Http;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class RateLimitIP extends RateLimit implements MiddlewareInterface
{
    /**
     * Only used in unit tests.
     */
    public static bool $active = true;

    private StorageInterface $storage;

    private Config $config;

    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;

    public function __construct(
        StorageInterface $storage,
        Config $config,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger,
    ) {
        $this->storage = $storage;
        $this->config = $config;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!self::$active) {
            return $handler->handle($request);
        }

        // Do not continue unless a fast storage driver is used,
        // especially one that does not need a database connection.
        if (get_class($this->storage) !== ApcuStorage::class) {
            return $handler->handle($request);
        }

        $maxRequests = (int) $this->config['rate_limit']['max'];
        $resetTime = (int) $this->config['rate_limit']['time'];
        if ($maxRequests === 0 || $resetTime === 0) {
            return $handler->handle($request);
        }

        $ip = Http::ipAddress();
        $key = Variables::RATE_LIMIT_IP . '_' . str_replace(['.', ':', ','], '', $ip);
        list($remaining, $resetIn, $numRequests, $elapsedTime) =
            $this->checkLimit($key, $this->storage, $maxRequests, $resetTime);

        if ($remaining < 0) {
            $appId = Http::appId();
            $appIdLog = empty($appId) ? '' : ", App-ID $appId";
            $this->logger->info(
                "IP Rate Limit: $ip$appIdLog, " .
                "limit exceeded with $numRequests request in $elapsedTime seconds.",
            );
            $response = $this->responseFactory->createResponse(429); // Too Many Requests
            $response->getBody()->write(
                "IP rate limit exceeded with $numRequests requests in $elapsedTime seconds.",
            );
        } else {
            $response = $handler->handle($request);
        }

        return $this->addHeader($response, $remaining, $resetIn);
    }
}
