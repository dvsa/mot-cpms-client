<?php

namespace CpmsClient\Service;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Utility\Util;
use DVSA\CPMS\Notifications\Messages\Values\PaymentNotificationV1;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use Exception;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Request;
use Laminas\Log\LoggerInterface;

/**
 * Class ApiService
 *
 * @package CpmsClient\Service
 */
class ApiService
{
    public const SCOPE_CARD         = 'CARD';
    public const SCOPE_CNP          = 'CNP';
    public const SCOPE_DIRECT_DEBIT = 'DIRECT_DEBIT';
    public const SCOPE_CHEQUE       = 'CHEQUE';
    public const SCOPE_REFUND       = 'REFUND';
    public const SCOPE_QUERY_TXN    = 'QUERY_TXN';
    public const SCOPE_STORED_CARD  = 'STORED_CARD';
    public const SCOPE_CHARGE_BACK  = 'CHARGE_BACK';
    public const SCOPE_CASH         = 'CASH';
    public const SCOPE_POSTAL_ORDER = 'POSTAL_ORDER';
    public const SCOPE_CHIP_PIN     = 'CHIP_PIN';
    public const SCOPE_ADJUSTMENT   = 'ADJUSTMENT';
    public const SCOPE_REPORT       = 'REPORT';
    public const CHEQUE_RD          = 'CHEQUE_RD'; // refer to drawer
    public const DIRECT_DEBIT_IC    = 'DIRECT_DEBIT_IC'; // indemnity claim
    public const REALLOCATE_PAYMENT = 'REALLOCATE'; // Reallocate payments by switch customer reference
    public const MAX_RETIRES        = 3;

    /** @var ClientOptions */
    protected $options;

    // we need to refactor the code to put these in a common package
    // that can be shared by both the client and the server :(
    public const CPMS_CODE_SUCCESS = '000';

    /**
     * Number of retries to get a valid token
     *
     * @var int
     */
    private static $retries = 0;

    public function __construct(
        protected LoggerInterface $logger,
        protected HttpRestJsonClient $client,
        protected StorageInterface $cacheStorage,
        protected bool $enableCache,
        protected NotificationsClient $queuesClient,
    ) {

        $this->options = $client->getOptions();
    }

    /**
     * Process API request
     *
     * @param string $endPointAlias
     * @param string $scope (CARD, DIRECT_DEBIT)
     * @param string $method HTTP Method (GET, POST, DELETE, PUT)
     * @param ?array $params
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    protected function processRequest($endPointAlias, $scope, $method, $params = null)
    {
        try {
            $salesReference = $this->getSalesReferenceFromParams($params);

            //Get access token
            $token = $this->getTokenForScope($scope, $salesReference);

            if ($token instanceof AccessToken) {
                $url                      = $this->getEndpoint($endPointAlias);
                $method                   = strtoupper($method);
                $headers                  = $this->getOptions()->getHeaders();
                $headers['Authorization'] = $token->getAuthorisationHeader();

                $this->getOptions()->setHeaders($headers);

                $params['customer_reference'] = $params['customer_reference'] ?? $this->options->getCustomerReference();
                $params['user_id'] = $params['user_id'] ?? $this->options->getUserId();

                /** @var array $return */
                $return = $this->getClient()->dispatchRequestAndDecodeResponse($url, $method, $params);

                if (empty($return)) {
                    return $this->returnErrorMessage($this->getClient()->getRequest());
                }

                /**
                 * Cache appears to have been deleted from the remote server but we have it cached locally
                 * We delete the local cache and try to get a valid access for token in 3 attempts
                 */
                if ($this->isCacheDeletedFromRemote($return)) {
                    self::$retries++;

                    $cacheKey = $this->generateCacheKey($scope, $salesReference);
                    $this->getCacheStorage()->removeItem($cacheKey);
                    $this->getClient()->resetHeaders();

                    $this->getLogger()->debug('Invalid access token retrying, attempt : ' . self::$retries);

                    return $this->processRequest($endPointAlias, $scope, $method, $params);
                }

                return $return;
            } else {
                return $token;
            }
        } catch (\Exception $exception) {
            return $this->returnErrorMessage(null, $exception);
        }
    }

    /**
     * Is the cache invalid
     *
     * @param array $return
     *
     * @return bool
     */
    protected function isCacheDeletedFromRemote($return)
    {
        return (self::$retries <= self::MAX_RETIRES
            && $this->getEnableCache()
            && isset($return['code'])
            && $return['code'] == AccessToken::INVALID_ACCESS_TOKEN
        );
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array $data
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function get($endPointAlias, $scope, $data = [])
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_GET, $data);
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array $data
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function post($endPointAlias, $scope, $data)
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_POST, $data);
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array $data
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function put($endPointAlias, $scope, $data)
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PUT, $data);
    }

    /**
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function patch(string $endPointAlias, string $scope, array $data)
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PATCH, $data);
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function delete($endPointAlias, $scope)
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_DELETE);
    }

    /**
     * Add header to request
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     * @throws Exception
     */
    public function addHeader($key, $value)
    {
        $headers       = $this->getOptions()->getHeaders();
        $headers[$key] = $value;
        $this->getOptions()->setHeaders($headers);

        return $this;
    }

    /**
     * @param StorageInterface $cacheStorage
     * @return void
     */
    public function setCacheStorage($cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @return StorageInterface
     */
    public function getCacheStorage()
    {
        return $this->cacheStorage;
    }

    /**
     * @param HttpRestJsonClient $client
     * @return void
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return HttpRestJsonClient
     * @throws Exception
     */
    public function getClient(): HttpRestJsonClient
    {
        return $this->client;
    }

    /**
     * @param ClientOptions $options
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return ClientOptions
     * @throws Exception
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $scope
     * @param ?string $salesReference
     *
     * @return array|AccessToken|string|null
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function getTokenForScope($scope, $salesReference = '')
    {
        $key = $this->generateCacheKey($scope, $salesReference);

        if ($this->getEnableCache() && $this->getCacheStorage()->hasItem($key)) {
            /** @var array $cache */
            $cache = $this->getCacheStorage()->getItem($key);
            $token = new AccessToken($cache);
        } else {
            $token = null;
        }

        if (empty($token) || $token->isExpired()) {
            /** @var array $data */
            $data = $this->getPaymentServiceAccessToken($scope, $salesReference);

            if (isset($data['access_token'])) {
                $data['issued_at'] = time();

                if ($this->getEnableCache()) {
                    $this->getCacheStorage()->setItem($key, $data);
                }
                $token = new AccessToken($data);
            } else {
                $this->getLogger()->warn('Unable to create access token with data: ' . print_r($data, true));

                return $data;
            }
        }

        return $token;
    }

    /**
     * @param string $scope
     * @param ?string $salesRef
     *
     * @return string
     * @throws Exception
     */
    public function generateCacheKey($scope, $salesRef = null)
    {
        return 'token-' . md5($scope . $salesRef . $this->getOptions()->getClientId());
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getEndpoint($key)
    {
        $endPoints = $this->getOptions()->getEndPoints();
        return $endPoints[$key] ?? $key;
    }

    /**
     * Make api request to get access token
     *
     * @param string $scope
     * @param ?string $salesReference
     *
     * @return mixed
     * @throws Exception
     */
    protected function getPaymentServiceAccessToken($scope, $salesReference = null)
    {
        $payload = [
            'client_id'     => $this->getOptions()->getClientId(),
            'client_secret' => $this->getOptions()->getClientSecret(),
            'user_id'       => $this->getOptions()->getUserId(),
            'grant_type'    => $this->getOptions()->getGrantType(),
            'scope'         => $scope,
        ];

        if ($salesReference !== '' && $salesReference !== null) {
            $payload['sales_reference'] = $salesReference;
        }

        // make sure that we do not send an 'Authorization' header when
        // asking for new auth token
        $client = $this->getClient();
        $client->resetHeaders();

        return $client->dispatchRequestAndDecodeResponse(
            $this->getEndpoint('access_token'),
            Request::METHOD_POST,
            $payload
        );
    }

    /**
     * @param boolean $enableCache
     * @return void
     */
    public function setEnableCache($enableCache)
    {
        $this->enableCache = $enableCache;
    }

    /**
     * @return bool
     */
    public function getEnableCache()
    {
        return $this->enableCache;
    }

    /**
     * @return array
     */
    private function returnErrorMessage(Request $request = null, Exception $exception = null)
    {
        $errorId   = $this->getErrorId();
        $message = [];
        $message[] = $errorId;

        if ($request) {
            $message[] = $request->toString();
        }

        if ($exception) {
            $message[] = Util::processException($exception);
        }

        $this->logger->err(implode(' ', $message));

        return array(
            'code'    => 105,
            'message' => sprintf("An CPMS client error occurred, ID %s\n%s", $errorId, implode('\n', $message)),
        );
    }

    /**
     * @param ?array $params
     *
     * @return ?string
     */
    private function getSalesReferenceFromParams($params)
    {
        if (!isset($params['payment_data'])) {
            return null;
        }

        $paymentRow = current($params['payment_data']);

        if (is_array($paymentRow) && isset($paymentRow['sales_reference'])) {
            return $paymentRow['sales_reference'];
        }

        return null;
    }

    /**
     * Set logger object
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger object
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Return a unique identifier for the error message for tracking in the the logs
     *
     * @return string
     */
    private function getErrorId()
    {
        return md5(uniqid('API'));
    }

    // ==================================================================
    //
    // Notification support
    //
    // ------------------------------------------------------------------

    /**
     * @return NotificationsClient
     */
    public function getNotificationsClient()
    {
        return $this->queuesClient;
    }

    /**
     * @param NotificationsClient $notificationsClient
     *
     * @return void
     */
    public function setNotificationsClient(NotificationsClient $notificationsClient)
    {
        $this->queuesClient = $notificationsClient;
    }

    /**
     * return a batch of pending notifications from the queue
     *
     * returns an empty array if:
     * - there is no queue client configured, or
     * - if the queue is currently empty
     *
     * returns an associative array of ['message', 'metadata'] pairs:
     * - 'message' is the notification from CPMS
     * - 'metadata' is information from the queueing system
     *
     * @return array
     */
    public function getNotifications()
    {
        return $this->queuesClient->getNotifications();
    }

    /**
     * call this when a notification has been applied to the scheme's
     * own data
     *
     * @param QueueMessage $metadata the metadata for the notification that has been applied
     * @param PaymentNotificationV1 $message the notification that has been applied
     *
     * @return void
     * @throws CpmsNotificationAcknowledgementFailed|ExceptionInterface
     * @throws Exception
     */
    public function acknowledgeNotification(QueueMessage $metadata, $message)
    {
        // shorthand
        $queuesClient = $this->getNotificationsClient();

        // contact cpms/payment-service, tell it that we have successfully
        // processed this notification
        /** @var array $response */
        $response = $this->put("/api/notifications/" . $message->getNotificationId() . '/acknowledged', 'NOTIFICATION', []);
        if (!isset($response['code']) || $response['code'] !== self::CPMS_CODE_SUCCESS) {
            $msg = "response from HttpClient does not contain expected 'code' field";
            $this->logger->warn($msg, $response);
            throw new CpmsNotificationAcknowledgementFailed($msg, $response);
        }

        // at this point, it is safe to delete the message from the queue
        $queuesClient->confirmMessageHandled($metadata);
    }
}
