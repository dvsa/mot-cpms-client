<?php

declare(strict_types=1);

namespace CpmsClientTest\Client;

use CpmsClient\Client\ClientOptions;
use CpmsClient\Client\HttpRestJsonClient;
use DvsaLogger\Logger\MotLogger;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CpmsClient\Client\HttpRestJsonClient
 */
class HttpRestJsonClientTest extends TestCase
{
    /**
     * @covers ::dispatchRequestAndDecodeResponse
     */
    public function testDispatchesGetDataAsQueryParameters(): void
    {
        [$client, $httpClient] = $this->createClient('{"result":"ok"}');

        $result = $client->dispatchRequestAndDecodeResponse(
            '/api/payments',
            Request::METHOD_GET,
            ['page' => 1, 'status' => 'open']
        );

        $this->assertSame(['result' => 'ok'], $result);
        $request = $httpClient->getRequest();
        $this->assertSame(Request::METHOD_GET, $request->getMethod());
        $this->assertSame('https://payments.example/api/payments', (string) $request->getUri());
        $query = $request->getQuery();
        $this->assertInstanceOf(Parameters::class, $query);
        $this->assertSame(['page' => 1, 'status' => 'open'], $query->toArray());

        $headers = $request->getHeaders();
        $this->assertInstanceOf(Headers::class, $headers);
        $contentType = $headers->get('Content-Type');
        $this->assertInstanceOf(HeaderInterface::class, $contentType);
        $this->assertSame(
            'application/vnd.dvsa-gov-uk.v2; charset=UTF-8',
            $contentType->getFieldValue()
        );
    }

    /**
     * @covers ::dispatchRequestAndDecodeResponse
     */
    public function testDispatchesNonGetDataAsJson(): void
    {
        [$client, $httpClient] = $this->createClient('{"accepted":true}');

        $result = $client->dispatchRequestAndDecodeResponse(
            'api/payments',
            Request::METHOD_POST,
            ['amount' => 12.5, 'currency' => 'GBP']
        );

        $this->assertSame(['accepted' => true], $result);
        $request = $httpClient->getRequest();
        $this->assertSame(Request::METHOD_POST, $request->getMethod());
        $this->assertSame(
            '{"amount":12.5,"currency":"GBP"}',
            $request->getContent()
        );

        $headers = $request->getHeaders();
        $this->assertInstanceOf(Headers::class, $headers);
        $contentType = $headers->get('Content-Type');
        $this->assertInstanceOf(HeaderInterface::class, $contentType);
        $this->assertSame(
            'application/vnd.dvsa-gov-uk.v2+json; charset=UTF-8',
            $contentType->getFieldValue()
        );
    }

    /**
     * @covers ::dispatchRequestAndDecodeResponse
     */
    public function testReturnsRawBodyForEmptyOrInvalidJson(): void
    {
        foreach (['', 'not-json'] as $body) {
            [$client] = $this->createClient($body);

            $result = $client->dispatchRequestAndDecodeResponse('/api/status', Request::METHOD_GET);

            $this->assertSame($body, $result);
        }
    }

    /**
     * @covers ::dispatchRequestAndDecodeResponse
     */
    public function testRequiresOptionsToBeInitialized(): void
    {
        [$clientWithoutOptions] = $this->createClient('{"ok":true}', true, false);
        $this->expectException(\LogicException::class);
        $clientWithoutOptions->dispatchRequestAndDecodeResponse('/api/status', Request::METHOD_GET);
    }

    /**
     * @covers ::dispatchRequestAndDecodeResponse
     */
    public function testRequiresRequestToBeInitialized(): void
    {
        [$clientWithoutRequest] = $this->createClient('{"ok":true}', false, true);
        $this->expectException(\LogicException::class);
        $clientWithoutRequest->dispatchRequestAndDecodeResponse('/api/status', Request::METHOD_GET);
    }

    /**
     * @param string $responseBody
     * @param bool $withRequest
     * @param bool $withOptions
     * @return array{0: HttpRestJsonClient, 1: HttpClient}
     */
    private function createClient(
        string $responseBody,
        bool $withRequest = true,
        bool $withOptions = true
    ): array {
        $adapter = new TestAdapter();
        $response = new Response();
        $response->setStatusCode(200);
        $response->setContent($responseBody);
        $adapter->setResponse($response);

        $httpClient = new HttpClient();
        $httpClient->setAdapter($adapter);
        $logger = $this->createMock(MotLogger::class);
        $request = $withRequest ? new Request() : null;
        $client = new HttpRestJsonClient($httpClient, $logger, $request);

        if ($withOptions) {
            $client->setOptions(new ClientOptions([
                'domain' => 'https://payments.example/',
                'version' => 2,
                'headers' => ['X-Test' => 'enabled'],
            ]));
        }

        return [$client, $httpClient];
    }
}
