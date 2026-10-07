<?php

declare(strict_types=1);

namespace CpmsClient\Client;

use CpmsClient\Utility\Util;
use DvsaLogger\Logger\MotLogger;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;

/**
 * Class HttpRestJsonClient
 *
 * @package CpmsClient\Client
 * @psalm-api
 */
class HttpRestJsonClient
{
    private const CONTENT_TYPE_FORMAT = 'application/vnd.dvsa-gov-uk.v%d%s; charset=UTF-8';

    private ?ClientOptions $options = null;

    public function __construct(
        private HttpClient $httpClient,
        private readonly MotLogger $logger,
        private ?Request $request = null
    ) {
    }

    /**
     * Dispatch request and decode json response
     *
     * @param string $url
     * @param string $method
     * @param array<string, mixed>|null $data
     *
     * @return mixed
     */
    public function dispatchRequestAndDecodeResponse(string $url, string $method, ?array $data = null): mixed
    {
        $this->logger->debug("[" . HttpRestJsonClient::class . "]: Starting request dispatch");
        $request = $this->getRequest();
        $options = $this->getOptions();

        if ($request === null || $options === null) {
            throw new \LogicException('HTTP client request and options must be initialized before dispatch.');
        }

        $request = clone $request;

        $headers = $options->getHeaders();
        $method  = strtoupper($method);

        if ($data) {
            if ($method == Request::METHOD_GET) {
                $contentType = sprintf(self::CONTENT_TYPE_FORMAT, $options->getVersion(), '');
                $request->setQuery(new Parameters($data));
            } else {
                $contentType = sprintf(self::CONTENT_TYPE_FORMAT, $options->getVersion(), '+json');
                $request->setContent(\json_encode($data));
            }
            $headers['Content-Type'] = $contentType;
        }

        $endpoint = rtrim($options->getDomain(), '/') . '/' . ltrim($url, '/');
        $endpoint = Util::appendQueryString($endpoint);

        /** @var Headers $requestHeaders */
        $requestHeaders = $request->getHeaders();
        $requestHeaders->addHeaders($headers);
        $request->setUri($endpoint);
        $request->setMethod($method);

        $this->logger->debug("[" . HttpRestJsonClient::class . "]: Dispatching request: " . $request->toString());

        /** @var Response $response */
        $response = $this->getHttpClient()->dispatch($request);

        $this->logger->debug("[" . HttpRestJsonClient::class . "]: Response status code: " . $response->getStatusCode());

        /** End User (Schemes) should interrogate response status,
         * throwing appropriate exceptions for error codes as required
         */
        $decodedData = \json_decode($response->getBody(), true);

        if (empty($decodedData)) {
            $this->logger->warn("[" . HttpRestJsonClient::class . "]: Response body is empty or not valid JSON. Returning raw response body.");

            return $response->getBody();
        }

        $this->logger->debug("[" . HttpRestJsonClient::class . "]: Response body decoded successfully.");
        return $decodedData;
    }

    /**
     * @param ClientOptions $options
     */
    public function setOptions($options): void
    {
        $this->options = $options;
    }

    public function getOptions(): ?ClientOptions
    {
        return $this->options;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @param HttpClient $httpClient
     */
    public function setHttpClient($httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * @return AbstractMessage
     */
    public function resetHeaders(): AbstractMessage
    {
        $options = $this->getOptions();

        if ($options === null) {
            throw new \LogicException('HTTP client options must be initialized before resetting headers.');
        }

        $headers = $options->getHeaders();

        if (isset($headers['Authorization'])) {
            unset($headers['Authorization']);
        }
        $options->setHeaders($headers);

        return $this->getHttpClient()->getRequest()->setHeaders(new Headers());
    }
}
