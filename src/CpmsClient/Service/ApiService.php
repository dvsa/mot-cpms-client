<?php

namespace CpmsClient\Service;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Utility\Util;
use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use Exception;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Request;
use Laminas\Log\LoggerInterface;

use function PHPUnit\Framework\arrayHasKey;

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

    protected LoggerInterface $logger;

    protected StorageInterface $cacheStorage;

    protected HttpRestJsonClient $client;

    protected array $tokens = [];

    protected ClientOptions $options;

    protected bool $enableCache = true;

    protected NotificationsClient $queuesClient;

    // we need to refactor the code to put these in a common package
    // that can be shared by both the client and the server :(
    public const CPMS_CODE_SUCCESS = '000';

    /**
     * Number of retries to get a valid token
     */
    private static int $retries = 0;

    public function __construct(
        LoggerInterface $logger,
        HttpRestJsonClient $httpRestJsonClient,
        StorageInterface $cache,
        bool $enableCache,
        NotificationsClient $notificationsClient
    ) {
        $this->logger = $logger;
        $this->client = $httpRestJsonClient;
        $this->options = $httpRestJsonClient->getOptions();
        $this->cacheStorage = $cache;
        $this->enableCache = $enableCache;
        $this->queuesClient = $notificationsClient;
    }

    /**
     * Process API request
     *
     * @param        $scope (CARD, DIRECT_DEBIT)
     * @param string $method HTTP Method (GET, POST, DELETE, PUT)
     *
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    protected function processRequest(string $endPointAlias, string $scope, string $method, array | null $params = null): mixed
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
     */
    protected function isCacheDeletedFromRemote(array $return): bool
    {
        return (self::$retries <= self::MAX_RETIRES
            && $this->getEnableCache()
            && isset($return['code'])
            && $return['code'] == AccessToken::INVALID_ACCESS_TOKEN
        );
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function get(string $endPointAlias, string $scope, array $data = array()): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_GET, $data);
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function post(string $endPointAlias, string $scope, array $data): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_POST, $data);
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function put(string $endPointAlias, string $scope, array $data): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PUT, $data);
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function patch(string $endPointAlias, string $scope, array $data): array | string
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PATCH, $data);
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function delete(string $endPointAlias, string $scope): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_DELETE);
    }

    /**
     * Add header to request
     * @throws Exception
     */
    public function addHeader(string $key, string $value): ApiService
    {
        $headers       = $this->getOptions()->getHeaders();
        $headers[$key] = $value;
        $this->getOptions()->setHeaders($headers);

        return $this;
    }

    public function setCacheStorage(StorageInterface $cacheStorage): void
    {
        $this->cacheStorage = $cacheStorage;
    }

    public function getCacheStorage(): StorageInterface
    {
        return $this->cacheStorage;
    }

    public function setClient(HttpRestJsonClient $client): void
    {
        $this->client = $client;
    }

    /**
     * @throws Exception
     */
    public function getClient(): HttpRestJsonClient
    {
        return $this->client;
    }

    public function setOptions(ClientOptions $options): void
    {
        $this->options = $options;
    }

    /**
     * @throws Exception
     */
    public function getOptions(): ClientOptions
    {
        return $this->options;
    }

    /**
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     * @throws Exception
     */
    public function getTokenForScope(string $scope, string | null $salesReference = ''): array | AccessToken | string | null
    {
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
     * @throws Exception
     */
    public function generateCacheKey(string $scope, string | null $salesRef = null): string
    {
        return 'token-' . md5($scope . $salesRef . $this->getOptions()->getClientId());
    }

    /**
     * @return string
     */
    public function getEndpoint(string $key): string
    {
        $endPoints = $this->getOptions()->getEndPoints();
        return $endPoints[$key] ?? $key;
    }

    /**
     * Make api request to get access token
     * @throws Exception
     */
    protected function getPaymentServiceAccessToken(string $scope, string | null $salesReference = null): mixed
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

    public function setEnableCache(bool $enableCache): void
    {
        $this->enableCache = $enableCache;
    }

    public function getEnableCache(): bool
    {
        return $this->enableCache;
    }

    private function returnErrorMessage(Request $request = null, Exception $exception = null): array
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

    private function getSalesReferenceFromParams(array | null $params): string | null
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
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger object
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Return a unique identifier for the error message for tracking in the the logs
     */
    private function getErrorId(): string
    {
        return md5(uniqid('API'));
    }

    // ==================================================================
    //
    // Notification support
    //
    // ------------------------------------------------------------------

    public function getNotificationsClient(): NotificationsClient
    {
        return $this->queuesClient;
    }

    public function setNotificationsClient(NotificationsClient $notificationsClient): void
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
     */
    public function getNotifications(): array
    {
        return $this->queuesClient->getNotifications();
    }

    /**
     * call this when a notification has been applied to the scheme's
     * own data
     *
     * @throws CpmsNotificationAcknowledgementFailed|ExceptionInterface
     * @throws Exception
     */
    public function acknowledgeNotification(QueueMessage $metadata, object $message): void
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
