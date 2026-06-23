<?php

namespace CpmsClient\Client;

use CpmsClient\Utility\Util;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Stdlib\Parameters;
use Psr\Log\LoggerInterface;

/**
 * Class HttpRestJsonClient
 *
 * @package CpmsClient\Client
 */
class HttpRestJsonClient
{
    const CONTENT_TYPE_FORMAT = 'application/vnd.dvsa-gov-uk.v%d%s; charset=UTF-8';

    /** @var \CpmsClient\Client\ClientOptions */
    protected $options;

    public function __construct(
        protected HttpClient $httpClient,
        protected LoggerInterface $logger,
        protected ?Request $request = null
    ) {
    }

    /**
     * Dispatch request and decode json response
     *
     * @param      $url
     * @param      $method
     * @param null $data
     *
     * @return mixed
     */
    public function dispatchRequestAndDecodeResponse($url, $method, $data = null)
    {
        $request = clone $this->getRequest();
        $headers = $this->options->getHeaders();
        $method  = strtoupper($method);

        if ($data) {
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

        $request->getHeaders()->addHeaders($headers);
        $request->setUri($endpoint);
        $request->setMethod($method);

        //Log request header
        $this->logger->debug($request->toString());

        /** @var \Laminas\Http\Response $response */
        $response = $this->getHttpClient()->dispatch($request);

        //log response code
        $this->logger->debug($response->getStatusCode());

        /** End User (Schemes) should interrogate response status,
         * throwing appropriate exceptions for error codes as required
         */
        $decodedData = \json_decode($response->getBody(), true);

        if (empty($decodedData)) {
            $this->logger->warning($response->getBody());

            return $response->getBody();
        }

        return $decodedData;
    }

    /**
     * @param $options
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
     * @param \Laminas\Http\Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return \Laminas\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param $httpClient
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
     * @return \Laminas\Http\AbstractMessage
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
