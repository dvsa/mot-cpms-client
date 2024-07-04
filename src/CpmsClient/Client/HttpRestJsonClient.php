<?php

namespace CpmsClient\Client;

use CaptainHook\App\Runner\Action\Cli;
use CpmsClient\Utility\Util;
use Exception;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Laminas\Stdlib\Parameters;
use phpDocumentor\Reflection\Types\Object_;

/**
 * Class HttpRestJsonClient
 *
 * @package CpmsClient\Client
 */
class HttpRestJsonClient
{
    protected const CONTENT_TYPE_FORMAT = 'application/vnd.dvsa-gov-uk.v%d%s; charset=UTF-8';
    protected HttpClient $httpClient;

    protected ClientOptions $options;

    protected Request $request;

    protected LoggerInterface $logger;

    public function __construct(HttpClient $httpClient, LoggerInterface $logger, Request $request)
    {
        $this->setHttpClient($httpClient);
        $this->setRequest($request);
        $this->logger = $logger;
        $this->options = new ClientOptions();
    }

    /**
     * Dispatch request and decode json response
     * @throws Exception
     */
    public function dispatchRequestAndDecodeResponse(string $url, string $method, array | null $data = null): mixed
    {
        $request = clone $this->getRequest();
        $headers = $this->options->getHeaders();
        $method  = strtoupper($method);

        if ($data !== null) {
            if ($method == Request::METHOD_GET) {
                $contentType = sprintf(self::CONTENT_TYPE_FORMAT, $this->getOptions()->getVersion(), '');
                $request->setQuery(new Parameters($data));
            } else {
                $contentType = sprintf(self::CONTENT_TYPE_FORMAT, $this->getOptions()->getVersion(), '+json');
                $request->setContent(\json_encode($data));
            }
            $headers['Content-Type'] = $contentType;
        }

        $endpoint = rtrim($this->options->getDomain(), '/') . '/' . ltrim($url, '/');
        $endpoint = Util::appendQueryString($endpoint);
        $headers = $request->getHeaders();
        if (is_object($headers) && get_class($headers) === Headers::class) {
            $headers->addHeaders($headers);
        }
        $request->setUri($endpoint);
        $request->setMethod($method);

        //Log request header
        $this->logger->debug($request->toString());

        $response = $this->getHttpClient()->dispatch($request);
        if (get_class($response) !== Response::class) {
            throw new Exception('HttpClient returned object not of class Response');
        }

        //log response code
        $this->logger->debug((string)$response->getStatusCode());

        /** End User (Schemes) should interrogate response status,
         * throwing appropriate exceptions for error codes as required
         */
        $decodedData = json_decode($response->getBody(), true);

        if (empty($decodedData)) {
            $this->logger->warn($response->getBody());

            return $response->getBody();
        }

        return $decodedData;
    }

    public function setOptions(ClientOptions $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): ClientOptions
    {
        return $this->options;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setHttpClient(HttpClient $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    public function resetHeaders(): AbstractMessage
    {
        $headers = $this->getOptions()->getHeaders();

        if (isset($headers['Authorization'])) {
            unset($headers['Authorization']);
        }
        $this->options->setHeaders($headers);

        return $this->getHttpClient()->getRequest()->setHeaders(new Headers());
    }
}
