<?php

declare(strict_types=1);

namespace CpmsClient\Service;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use CpmsClient\Client\NotificationsClient;
use CpmsClient\Data\AccessToken;
use CpmsClient\Exceptions\CpmsNotificationAcknowledgementFailed;
use CpmsClient\Utility\Util;
use DVSA\CPMS\Queues\QueueAdapters\Interfaces\Queues;
use DVSA\CPMS\Queues\QueueAdapters\Values\QueueMessage;
use DvsaLogger\Logger\MotLogger;
use Exception;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\Request;
use Laminas\ServiceManager\ServiceManager;

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

    protected ?MotLogger $logger = null;

    protected StorageInterface $cacheStorage;
    /**
     * @var HttpRestJsonClient
     */
    protected HttpRestJsonClient $client;

    protected ClientOptions $options;

    /** @var bool */
    protected bool $enableCache = true;

    protected ?NotificationsClient $queuesClient = null;

    // we need to refactor the code to put these in a common package
    // that can be shared by both the client and the server :(
    public const CPMS_CODE_SUCCESS = '000';

    /**
     * Number of retries to get a valid token
     *
     * @var int
     */
    private static int $retries = 0;

    /**
     * Process API request
     *
     * @param string $endPointAlias
     * @param string $scope (CARD, DIRECT_DEBIT)
     * @param string $method HTTP Method (GET, POST, DELETE, PUT)
     * @param array<string, mixed>|null $params
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    protected function processRequest(string $endPointAlias, string $scope, string $method, ?array $params = null): mixed
    {
        $this->logger?->info("Starting processing request for endpoint: $endPointAlias, scope: $scope, method: $method");

        try {
            $salesReference = $this->getSalesReferenceFromParams($params);
            $params         ??= [];

            //Get access token
            $token = $this->getTokenForScope($scope, $salesReference);

            if ($token instanceof AccessToken) {
                $url                      = $this->getEndpoint($endPointAlias);
                $method                   = strtoupper($method);
                $headers                  = $this->getOptions()->getHeaders();
                $headers['Authorization'] = $token->getAuthorisationHeader();

                $this->getOptions()->setHeaders($headers);

                if (empty($params['customer_reference'])) {
                    $params['customer_reference'] = $this->options->getCustomerReference();
                }

                if (empty($params['user_id'])) {
                    $params['user_id'] = $this->options->getUserId();
                }

                $decodedResponse = $this->getClient()->dispatchRequestAndDecodeResponse($url, $method, $params);

                if (empty($decodedResponse)) {
                    return $this->returnErrorMessage($this->getClient()->getRequest());
                }

                /**
                 * Cache appears to have been deleted from the remote server but we have it cached locally
                 * We delete the local cache and try to get a valid access for token in 3 attempts
                 */
                if ($this->isCacheDeletedFromRemote($decodedResponse)) {
                    self::$retries++;

                    $cacheKey = $this->generateCacheKey($scope, $salesReference);
                    $this->getCacheStorage()->removeItem($cacheKey);
                    $this->getClient()->resetHeaders();

                    $this->getLogger()?->debug('Invalid access token retrying, attempt : ' . self::$retries);

                    return $this->processRequest($endPointAlias, $scope, $method, $params);
                }

                $this->logger?->info("Request processed successfully for endpoint: $endPointAlias, scope: $scope, method: $method");
                return $decodedResponse;
            } else {
                return $token;
            }
        } catch (\Exception $exception) {
            $this->logger?->error("Exception occurred while processing request for endpoint: $endPointAlias, scope: $scope, method: $method. Exception: " . $exception->getMessage());
            return $this->returnErrorMessage(null, $exception);
        }
    }

    /**
     * Is the cache invalid
     *
     * @param mixed $return
     *
     * @return bool
     */
    protected function isCacheDeletedFromRemote(mixed $return): bool
    {
        return (self::$retries <= self::MAX_RETIRES
            && $this->getEnableCache()
            && is_array($return)
            && isset($return['code'])
            && $return['code'] == AccessToken::INVALID_ACCESS_TOKEN
        );
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array<string, mixed> $data
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function get(string $endPointAlias, string $scope, array $data = []): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_GET, $data);
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array<string, mixed> $data
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function post(string $endPointAlias, string $scope, array $data): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_POST, $data);
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array<string, mixed> $data
     *
     * @return array|mixed
     * @throws ExceptionInterface
     */
    public function put(string $endPointAlias, string $scope, array $data): mixed
    {
        return $this->processRequest($endPointAlias, $scope, Request::METHOD_PUT, $data);
    }

    /**
     * @param string $endPointAlias
     * @param string $scope
     * @param array<string, mixed> $data
     * @return mixed
     * @throws ExceptionInterface
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function patch(string $endPointAlias, string $scope, array $data): mixed
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
    public function delete(string $endPointAlias, string $scope): mixed
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
    public function addHeader(string $key, string $value): static
    {
        $headers       = $this->getOptions()->getHeaders();
        $headers[$key] = $value;
        $this->getOptions()->setHeaders($headers);

        return $this;
    }

    /**
     * @param StorageInterface $cacheStorage
     */
    public function setCacheStorage(StorageInterface $cacheStorage): void
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @return StorageInterface
     */
    public function getCacheStorage(): StorageInterface
    {
        return $this->cacheStorage;
    }

    /**
     * @param HttpRestJsonClient $client
     */
    public function setClient(HttpRestJsonClient $client): void
    {
        $this->client = $client;
    }

    /**
     * @return HttpRestJsonClient
     */
    public function getClient(): HttpRestJsonClient
    {
        return $this->client;
    }

    /**
     * @param ClientOptions $options
     */
    public function setOptions(ClientOptions $options): void
    {
        $this->options = $options;
    }

    /**
     * @return ClientOptions
     */
    public function getOptions(): ClientOptions
    {
        return $this->options;
    }

    /**
     * @return mixed
     * @throws ExceptionInterface
     */
    public function getTokenForScope(string $scope, ?string $salesReference = '')
    {
        $key = $this->generateCacheKey($scope, $salesReference);

        if ($this->getEnableCache() && $this->getCacheStorage()->hasItem($key)) {
            $cache = $this->getCacheStorage()->getItem($key);
            /** @var array<string, mixed> $cache */
            $token = is_array($cache) ? new AccessToken($cache) : null;
        } else {
            $token = null;
        }

        if (empty($token) || $token->isExpired()) {
            $data = $this->getPaymentServiceAccessToken($scope, $salesReference);

            if (is_array($data) && isset($data['access_token'])) {
                /** @var array<string, mixed> $data */
                $data['issued_at'] = time();

                if ($this->getEnableCache()) {
                    $this->getCacheStorage()->setItem($key, $data);
                }
                $token = new AccessToken($data);
            } else {
                $this->getLogger()?->warn('Unable to create access token with data: ' . print_r($data, true));
                return $data;
            }
        }

        return $token;
    }

    /**
     * @param string $scope
     * @param string|null $salesRef
     *
     * @return string
     */
    public function generateCacheKey(string $scope, ?string $salesRef = null): string
    {
        return 'token-' . md5($scope . $salesRef . $this->getOptions()->getClientId());
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getEndpoint(string $key): string
    {
        $endPoints = $this->getOptions()->getEndPoints();
        if (isset($endPoints[$key]) && is_string($endPoints[$key])) {
            return $endPoints[$key];
        } else {
            return $key;
        }
    }

    /**
     * Make api request to get access token
     *
     * @param string $scope
     * @param string|null $salesReference
     * @return mixed
     */
    protected function getPaymentServiceAccessToken(string $scope, ?string $salesReference = null): mixed
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
    public function setEnableCache(bool $enableCache): void
    {
        $this->enableCache = $enableCache;
    }

    /**
     * @return bool
     */
    public function getEnableCache(): bool
    {
        return $this->enableCache;
    }

    /**
     * @param Request|null $request
     * @param Exception|null $exception
     *
     * @return array<string, mixed>
     */
    private function returnErrorMessage(Request $request = null, Exception $exception = null): array
    {
        $errorId   = $this->getErrorId();
        $message[] = $errorId;

        if ($request) {
            $message[] = $request->toString();
        }

        if ($exception) {
            $message[] = Util::processException($exception);
        }

        $this->logger?->error("An CPMS client error occurred, ID $errorId\n" . implode('\\n', $message));

        return array(
            'code'    => 105,
            'message' => sprintf("An CPMS client error occurred, ID %s\n%s", $errorId, implode('\n', $message)),
        );
    }

    /**
     * @param array<string, mixed>|null $params
     *
     * @return string|null
     */
    private function getSalesReferenceFromParams(?array $params): ?string
    {
        if (!isset($params['payment_data']) || !is_array($params['payment_data'])) {
            return null;
        }

        $paymentRow = current($params['payment_data']);

        if (is_array($paymentRow) && isset($paymentRow['sales_reference']) && is_string($paymentRow['sales_reference'])) {
            return $paymentRow['sales_reference'];
        }

        return null;
    }

    /**
     * Set logger object
     *
     * @param MotLogger $logger
     *
     * @return static
     */
    public function setLogger(MotLogger $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger object
     *
     * @return MotLogger|null
     */
    public function getLogger(): ?MotLogger
    {
        return $this->logger;
    }

    /**
     * Return a unique identifier for the error message for tracking in the the logs
     *
     * @return string
     */
    private function getErrorId(): string
    {
        return md5(uniqid('API'));
    }

    /**
     * @return NotificationsClient|null
     */
    public function getNotificationsClient(): ?NotificationsClient
    {
        return $this->queuesClient;
    }

    /**
     * @param NotificationsClient $notificationsClient
     */
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
     *
     * @return array<int, array{metadata: QueueMessage, message: object}>
     */
    public function getNotifications(): array
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
     * @param QueueMessage $metadata
     *         the metadata for the notification that has been applied
     * @param object $message
     *         the notification that has been applied
     * @return void
     * @throws CpmsNotificationAcknowledgementFailed
     * @throws ExceptionInterface
     */
    public function acknowledgeNotification(QueueMessage $metadata, object $message): void
    {
        /* @var NotificationsClient|null $queuesClient */
        $queuesClient = $this->getNotificationsClient();

        // contact cpms/payment-service, tell it that we have successfully
        // processed this notification
        if (!method_exists($message, 'getNotificationId')) {
            throw new CpmsNotificationAcknowledgementFailed('Notification does not expose an ID', []);
        }

        $notificationIdValue = call_user_func([$message, 'getNotificationId']);
        if (!is_scalar($notificationIdValue)) {
            throw new CpmsNotificationAcknowledgementFailed('Notification ID is not scalar', []);
        }
        $notificationId = (string) $notificationIdValue;
        $response = $this->put("/api/notifications/" . $notificationId . '/acknowledged', 'NOTIFICATION', []);
        if (!is_array($response) || !isset($response['code']) || $response['code'] !== self::CPMS_CODE_SUCCESS) {
            $msg = "response from HttpClient does not contain expected 'code' field";
            $this->logger?->warn($msg, is_array($response) ? $response : []);
            throw new CpmsNotificationAcknowledgementFailed($msg, $response);
        }

        // at this point, it is safe to delete the message from the queue
        $queuesClient?->confirmMessageHandled($metadata);
    }
}
