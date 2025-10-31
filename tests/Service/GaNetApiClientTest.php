<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Exception\GaNetApiException;
use Tourze\GaNetBundle\Service\GaNetApiClient;

/**
 * @internal
 */
#[CoversClass(GaNetApiClient::class)]
final class GaNetApiClientTest extends TestCase
{
    private HttpClientInterface $httpClient;

    private CacheInterface&MockObject $cache;

    private GaNetApiClient $apiClient;

    private Publisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->apiClient = new GaNetApiClient($this->httpClient, $this->cache);
        $this->publisher = new Publisher();
        $this->publisher->setPublisherId(12345);
        $this->publisher->setToken('test-token-12345');
    }

    #[Test]
    public function testGetCampaignListShouldReturnSuccessfulResponse(): void
    {
        $expectedResponse = ['response' => 200, 'data' => ['campaigns' => []]];
        $mockResponse = new MockResponse((string) json_encode($expectedResponse));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $result = $apiClient->getCampaignList($this->publisher, 1001, 0);

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    public function testGetCampaignListShouldIncludeCorrectParameters(): void
    {
        $expectedResponse = ['response' => 200, 'data' => []];
        $mockResponse = new MockResponse((string) json_encode($expectedResponse));

        $mockHttpClient = new MockHttpClient(function ($method, $url, $options) use ($mockResponse) {
            // 验证请求参数
            if (!is_array($options)) {
                return $mockResponse;
            }
            $query = $options['query'] ?? [];
            if (!is_array($query)) {
                return $mockResponse;
            }
            self::assertEquals(1001, $query['website_id']);
            self::assertEquals(5, $query['status']);
            self::assertArrayHasKey('timestamp', $query);
            self::assertArrayHasKey('sign', $query);
            self::assertArrayHasKey('token', $query);

            return $mockResponse;
        });

        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);
        $apiClient->getCampaignList($this->publisher, 1001, 5);
    }

    #[Test]
    public function testGetCommissionListShouldReturnSuccessfulResponse(): void
    {
        $expectedResponse = ['response' => 200, 'data' => ['commissions' => []]];
        $mockResponse = new MockResponse((string) json_encode($expectedResponse));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $result = $apiClient->getCommissionList($this->publisher, 1001, 67890);

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    public function testGetTransactionReportShouldReturnSuccessfulResponse(): void
    {
        $expectedResponse = ['response' => 200, 'data' => ['transactions' => []]];
        $mockResponse = new MockResponse((string) json_encode($expectedResponse));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $result = $apiClient->getTransactionReport(
            $this->publisher,
            '2024-01-01 00:00:00',
            '2024-01-31 23:59:59'
        );

        $this->assertSame($expectedResponse, $result);
    }

    #[Test]
    public function testGetTransactionReportShouldIncludeOrderStatusWhenProvided(): void
    {
        $expectedResponse = ['response' => 200, 'data' => []];
        $mockResponse = new MockResponse((string) json_encode($expectedResponse));

        $mockHttpClient = new MockHttpClient(function ($method, $url, $options) use ($mockResponse) {
            // 验证订单状态参数
            if (is_array($options) && isset($options['query']) && is_array($options['query'])) {
                self::assertEquals(2, $options['query']['order_status']);
            }

            return $mockResponse;
        });

        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);
        $apiClient->getTransactionReport(
            $this->publisher,
            '2024-01-01 00:00:00',
            '2024-01-31 23:59:59',
            2
        );
    }

    #[Test]
    public function testApiErrorResponseShouldThrowException(): void
    {
        $errorResponse = ['response' => '01']; // 字段缺失错误
        $mockResponse = new MockResponse((string) json_encode($errorResponse));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $this->expectException(GaNetApiException::class);
        $this->expectExceptionMessage('API返回错误：字段缺失 (错误码: 01)');

        $apiClient->getCampaignList($this->publisher, 1001, 0);
    }

    #[Test]
    public function testHttpErrorShouldThrowException(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 404]);

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $this->expectException(GaNetApiException::class);
        $this->expectExceptionMessage('API请求失败，HTTP状态码: 404');

        $apiClient->getCampaignList($this->publisher, 1001, 0);
    }

    #[Test]
    public function testInvalidJsonResponseShouldThrowException(): void
    {
        $mockResponse = new MockResponse('invalid json');

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $this->expectException(GaNetApiException::class);
        $this->expectExceptionMessage('JSON解析失败');

        $apiClient->getCampaignList($this->publisher, 1001, 0);
    }

    #[Test]
    public function testMissingResponseFieldShouldThrowException(): void
    {
        $invalidResponse = ['data' => []]; // 缺少response字段
        $mockResponse = new MockResponse((string) json_encode($invalidResponse));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $this->expectException(GaNetApiException::class);
        $this->expectExceptionMessage('API响应格式错误：缺少response字段');

        $apiClient->getCampaignList($this->publisher, 1001, 0);
    }

    #[Test]
    public function testGetTransactionBalanceReportShouldUseCaching(): void
    {
        $cachedData = ['response' => 200, 'data' => ['balance' => '1000.00']];

        $cacheExpectation = $this->cache->expects(self::once())
            ->method('get')
            ->with(
                self::stringContains('ga_net_transaction_balance_12345_2024-01'),
                self::callback(function ($callback) {
                    return is_callable($callback);
                }),
                86400 // 24小时缓存
            )
        ;
        $cacheExpectation->willReturn($cachedData);

        $result = $this->apiClient->getTransactionBalanceReport($this->publisher, '2024-01');

        $this->assertSame($cachedData, $result);
    }

    #[Test]
    public function testErrorCodeMappingShouldReturnCorrectMessages(): void
    {
        $testCases = [
            '01' => '字段缺失',
            '02' => 'publisher id 错误',
            '03' => 'token缺失',
            '04' => '签名错误',
            '05' => '时间戳错误',
            '99' => '未知错误',
        ];

        foreach ($testCases as $errorCode => $expectedMessage) {
            $errorResponse = ['response' => $errorCode];
            $mockResponse = new MockResponse((string) json_encode($errorResponse));

            $this->httpClient = new MockHttpClient($mockResponse);
            $apiClient = new GaNetApiClient($this->httpClient, $this->cache);

            try {
                $apiClient->getCampaignList($this->publisher, 1001, 0);
                self::fail('Expected GaNetApiException to be thrown');
            } catch (GaNetApiException $e) {
                self::assertStringContainsString($expectedMessage, $e->getMessage());
                self::assertStringContainsString(strval($errorCode), $e->getMessage());
            }
        }
    }

    #[Test]
    public function testGetCampaignDealsShouldReturnSuccessfulResponse(): void
    {
        $expectedResponse = ['response' => 200, 'data' => ['deals' => []]];
        $mockResponse = new MockResponse((string) json_encode($expectedResponse));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $apiClient = new GaNetApiClient($mockHttpClient, $this->cache);

        $result = $apiClient->getCampaignDeals($this->publisher, 1001, 67890);

        $this->assertSame($expectedResponse, $result);
    }
}
