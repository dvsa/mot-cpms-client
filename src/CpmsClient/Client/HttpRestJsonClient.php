<?php

namespace CpmsClient\Client;

use CpmsClient\Utility\Util;
use Exception;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Laminas\Stdlib\Parameters;

/**
 * Class HttpRestJsonClient
 *
 * @package CpmsClient\Client
 */
class HttpRestJsonClient
{
    protected const CONTENT_TYPE_FORMAT = 'application/vnd.dvsa-gov-uk.v%d%s; charset=UTF-8';
    /** @var HttpClient */
    protected $httpClient;

    /**
     * @var ClientOptions
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $options;

    /** @var Request */
    protected $request;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(HttpClient $httpClient, LoggerInterface $logger, Request $request)
    {
        $this->setHttpClient($httpClient);
        $this->setRequest($request);
        $this->logger = $logger;
    }

    /**
     * Dispatch request and decode json response
     *
     * @param string $url
     * @param string $method
     * @param ?array $data
     *
     * @return mixed
     * @throws Exception
     */
    public function dispatchRequestAndDecodeResponse($url, $method, $data = null)
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

    /**
     * @param ClientOptions $options
     *
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return ClientOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param HttpClient $httpClient
     *
     * @return void
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @return AbstractMessage
     */
    public function resetHeaders()
    {
        $headers = $this->getOptions()->getHeaders();

        if (isset($headers['Authorization'])) {
            unset($headers['Authorization']);
        }
        $this->options->setHeaders($headers);

        return $this->getHttpClient()->getRequest()->setHeaders(new Headers());
    }
}
