<?php
namespace CpmsClient\Service;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Utility\Util;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use Exception;
use Laminas\Http\Request;
use Laminas\Log\Logger;
use Laminas\Log\LoggerInterface;

/**
 * Class ApiService
 *
 * @package CpmsClient\Service
 */
class ApiService
{
    const SCOPE_CARD         = 'CARD';
    const SCOPE_CNP          = 'CNP';
    const SCOPE_DIRECT_DEBIT = 'DIRECT_DEBIT';
    const SCOPE_CHEQUE       = 'CHEQUE';
    const SCOPE_REFUND       = 'REFUND';
    const SCOPE_QUERY_TXN    = 'QUERY_TXN';
    const SCOPE_STORED_CARD  = 'STORED_CARD';
    const SCOPE_CHARGE_BACK  = 'CHARGE_BACK';
    const SCOPE_CASH         = 'CASH';
    const SCOPE_POSTAL_ORDER = 'POSTAL_ORDER';
    const SCOPE_CHIP_PIN     = 'CHIP_PIN';
    const SCOPE_ADJUSTMENT   = 'ADJUSTMENT';
    const SCOPE_REPORT       = 'REPORT';
    const CHEQUE_RD          = 'CHEQUE_RD'; // refer to drawer
    const DIRECT_DEBIT_IC    = 'DIRECT_DEBIT_IC'; // indemnity claim
    const REALLOCATE_PAYMENT = 'REALLOCATE'; // Reallocate payments by switch customer reference
    const MAX_RETIRES        = 3;
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /** @var  \Laminas\Cache\Storage\StorageInterface */
    protected $cacheStorage;
    /**
     * @var \CpmsClient\Client\HttpRestJsonClient
     */
    protected $client;

    /** @var  \Laminas\ServiceManager\ServiceManager */
    protected $serviceManager;

    /** @var array */
    protected $tokens = array();

    /** @var ClientOptions */
    protected $options;

    /** @var bool */
    protected $enableCache = true;

    /** @var \DVSA\CPMS\Queues\QueueAdapters\Queues */
    protected $queuesClient;

    // we need to refactor the code to put these in a common package
    // that can be shared by both the client and the server :(
    const CPMS_CODE_SUCCESS = '000';

    /**
     * Number of retries to get a valid token
     *
     * @var int
     */
    private static $retries = 0;

    /**
     * Process API request
     *
     * @param        $endPointAlias
     * @param        $scope (CARD, DIRECT_DEBIT)
     * @param string $method HTTP Method (GET, POST, DELETE, PUT)
     * @param null $params
     *
     * @return array|mixed
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    protected function processRequest($endPointAlias, $scope, $method, $params = null)
    {
        try {
            $method         = (string)$method;
            $scope          = (string)$scope;
            $salesReference = $this->getSalesReferenceFromParams($params);

            //Get access token
            $token = $this->getTokenForScope($scope, $salesReference);

            if ($token instanceof AccessToken) {
                $url                      = $this->getEndpoint($endPointAlias);
                $method                   = strtoupper($method);
                $headers                  = $this->getOptions()->getHeaders();
                $headers['Authorization'] = $token->getAuthorisationHeader();

                $this->getOptions()->setHeaders($headers);

                if (empty($data['customer_reference'])) {
                    $data['customer_reference'] = $this->options->getCustomerReference();
                }

                if (empty($data['user_id'])) {
                    $data['user_id'] = $this->options->getUserId();
                }

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
     * @param $return
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
     * @param       $endPointAlias
     * @param       $scope
     * @param array $data
     *
     * @return array|mixed
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function get($endPointAlias, $scope, $data = array())
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_GET, $data);
    }

    /**
     * @param $endPointAlias
     * @param $scope
     * @param $data
     *
     * @return array|mixed
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function post($endPointAlias, $scope, $data)
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_POST, $data);
    }

    /**
     * @param $endPointAlias
     * @param $scope
     * @param $data
     *
     * @return array|mixed
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function put($endPointAlias, $scope, $data)
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PUT, $data);
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function patch(string $endPointAlias, string $scope, array $data): array|string
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PATCH, $data);
    }

    /**
     * @param $endPointAlias
     * @param $scope
     *
     * @return array|mixed
     * @throws \Laminas\Cache\Exception\ExceptionInterface
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
     */
    public function addHeader($key, $value)
    {
        $headers       = $this->getOptions()->getHeaders();
        $headers[$key] = $value;
        $this->getOptions()->setHeaders($headers);

        return $this;
    }

    /**
     * @param \Laminas\Cache\Storage\StorageInterface $cacheStorage
     */
    public function setCacheStorage($cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @return \Laminas\Cache\Storage\StorageInterface
     */
    public function getCacheStorage()
    {
        return $this->cacheStorage;
    }

    /**
     * @param HttpRestJsonClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return HttpRestJsonClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param ClientOptions $options
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
     * @param $scope
     * @param string $salesReference
     *
     * @return AccessToken
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function getTokenForScope($scope, $salesReference = '')
    {
        /** @var \CpmsClient\Data\AccessToken $token */
        $key = $this->generateCacheKey($scope, $salesReference);

        if ($this->getEnableCache() && $this->getCacheStorage()->hasItem($key)) {
            $cache = $this->getCacheStorage()->getItem($key);
            $token = new AccessToken($cache);
        } else {
            $token = null;
        }

        if (empty($token) || $token->isExpired()) {
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
     * @param $scope
     * @param $salesRef
     *
     * @return string
     */
    public function generateCacheKey($scope, $salesRef = null)
    {
        return 'token-' . md5($scope . $salesRef . $this->getOptions()->getClientId());
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function getEndpoint($key)
    {
        $endPoints = $this->getOptions()->getEndPoints();
        if (isset($endPoints[$key])) {
            return $endPoints[$key];
        } else {
            return $key;
        }
    }

    /**
     * Make api request to get access token
     *
     * @param $scope
     * @param $salesReference
     *
     * @return mixed
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

        if (!empty($salesReference)) {
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
     * @param Request   $request
     * @param Exception $exception
     *
     * @return array
     */
    private function returnErrorMessage(Request $request = null, Exception $exception = null)
    {
        $errorId   = $this->getErrorId();
        $message[] = $errorId;

        if ($request) {
            $message[] = $request->toString();
        }

        if ($exception) {
            $message[] = Util::processException($exception);
        }

        if ($logger = $this->getLogger()) {
            $logger->err(implode(' ', $message));
        }

        return array(
            'code'    => 105,
            'message' => sprintf("An CPMS client error occurred, ID %s\n%s", $errorId, implode('\n', $message)),
        );
    }

    /**
     * @param $params
     *
     * @return string|null
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
     * @param Logger $logger
     *
     * @return mixed
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger object
     *
     * @return  Logger
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
     * @return NotificationsClient|null
     */
    public function getNotificationsClient()
    {
        return $this->queuesClient;
    }

    /**
     * @param NotificationsClient $client
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
        // if we have no queues client, there are no notifications to get
        if ($this->queuesClient === null) {
            return [];
        }

        return $this->queuesClient->getNotifications();
    }

    /**
     * call this when a notification has been applied to the scheme's
     * own data
     *
     * @param  QueueMessage $metadata
     *         the metadata for the notification that has been applied
     * @param  object $message
     *         the notification that has been applied
     * @return void
     */
    public function acknowledgeNotification(QueueMessage $metadata, $message)
    {
        // shorthand
        $queuesClient = $this->getNotificationsClient();

        // contact cpms/payment-service, tell it that we have successfully
        // processed this notification
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
