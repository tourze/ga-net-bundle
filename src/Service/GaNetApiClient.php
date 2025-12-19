<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Exception\GaNetApiException;

readonly final class GaNetApiClient
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
    ) {
        $domain = $_ENV['GA_NET_DOMAIN'] ?? 'https://www.ga-net.com';
        if (!is_string($domain)) {
            $domain = 'https://www.ga-net.com';
        }
        $this->baseUrl = $domain . '/api';
    }

    /**
     * 活动列表API - campaign_list
     * @param Publisher $publisher 发布者
     * @param int $websiteId 网站ID
     * @param int $status 申请状态 (0: 所有, -1: 未申请, 1: 申请中,5: 已通过,6: 已拒绝)
     * @return array<mixed>
     */
    public function getCampaignList(Publisher $publisher, int $websiteId, int $status = 0): array
    {
        $timestamp = time();
        $params = [
            'website_id' => $websiteId,
            'status' => $status,
            'timestamp' => $timestamp,
            'sign' => $publisher->generateSign($timestamp),
            'token' => $publisher->getToken(),
        ];

        $response = $this->httpClient->request('GET', sprintf('%s/campaign_list/%d', $this->baseUrl, $publisher->getPublisherId()), [
            'query' => $params,
            'timeout' => 30,
        ]);

        return $this->parseResponse($response);
    }

    /**
     * 佣金查询API - commission_list
     * @param Publisher $publisher 发布者
     * @param int $websiteId 网站ID
     * @param int $campaignId 活动ID
     * @return array<mixed>
     */
    public function getCommissionList(Publisher $publisher, int $websiteId, int $campaignId): array
    {
        $timestamp = time();
        $params = [
            'website_id' => $websiteId,
            'campaign_id' => $campaignId,
            'timestamp' => $timestamp,
            'sign' => $publisher->generateSign($timestamp),
            'token' => $publisher->getToken(),
        ];

        $response = $this->httpClient->request('GET', sprintf('%s/commission_list/%d', $this->baseUrl, $publisher->getPublisherId()), [
            'query' => $params,
            'timeout' => 30,
        ]);

        return $this->parseResponse($response);
    }

    /**
     * 订单查询API - transaction_report
     * @param Publisher $publisher 发布者
     * @param string $startTime 开始时间 (UTC时区，格式: 2019-01-01 00:00:00)
     * @param string $endTime 结束时间 (UTC时区，格式: 2019-02-01 23:59:59)
     * @param int|null $orderStatus 订单状态（可选）1 => 待认证, 2 => 已认证, 3 => 拒绝
     * @return array<mixed>
     */
    public function getTransactionReport(Publisher $publisher, string $startTime, string $endTime, ?int $orderStatus = null): array
    {
        $timestamp = time();
        $params = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'timestamp' => $timestamp,
            'sign' => $publisher->generateSign($timestamp),
            'token' => $publisher->getToken(),
        ];

        if (null !== $orderStatus) {
            $params['order_status'] = $orderStatus;
        }

        $response = $this->httpClient->request('GET', sprintf('%s/transaction_report/%d', $this->baseUrl, $publisher->getPublisherId()), [
            'query' => $params,
            'timeout' => 30,
        ]);

        return $this->parseResponse($response);
    }

    /**
     * 优惠促销API - campaign_deals
     * @param Publisher $publisher 发布者
     * @param int $websiteId 网站ID
     * @param int $campaignId 活动ID
     * @return array<mixed>
     */
    public function getCampaignDeals(Publisher $publisher, int $websiteId, int $campaignId): array
    {
        $timestamp = time();
        $params = [
            'website_id' => $websiteId,
            'campaign_id' => $campaignId,
            'timestamp' => $timestamp,
            'sign' => $publisher->generateSign($timestamp),
            'token' => $publisher->getToken(),
        ];

        $response = $this->httpClient->request('GET', sprintf('%s/campaign_deals/%d', $this->baseUrl, $publisher->getPublisherId()), [
            'query' => $params,
            'timeout' => 30,
        ]);

        return $this->parseResponse($response);
    }

    /**
     * 结算查询API - transaction_balance_report
     * 注意：通过缓存层面避免重复请求
     * @param Publisher $publisher 发布者
     * @param string $month 结算月份 (格式: 2019-02)
     * @return array<mixed>
     * @throws InvalidArgumentException
     */
    public function getTransactionBalanceReport(Publisher $publisher, string $month): array
    {
        $cacheKey = sprintf('ga_net_transaction_balance_%d_%s', $publisher->getPublisherId(), $month);

        return $this->cache->get($cacheKey, function () use ($publisher, $month) {
            $timestamp = time();
            $params = [
                'month' => $month,
                'timestamp' => $timestamp,
                'sign' => $publisher->generateSign($timestamp),
                'token' => $publisher->getToken(),
            ];

            $response = $this->httpClient->request('GET', sprintf('%s/transaction_balance_report/%d', $this->baseUrl, $publisher->getPublisherId()), [
                'query' => $params,
                'timeout' => 30,
            ]);

            return $this->parseResponse($response);
        }, 86400); // 24小时缓存
    }

    /**
     * 解析API响应
     * @param ResponseInterface $response HTTP响应
     * @return array<mixed>
     * @throws \Exception
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            throw new GaNetApiException(sprintf('API请求失败，HTTP状态码: %d', $statusCode));
        }

        $content = $response->getContent();
        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new GaNetApiException(sprintf('JSON解析失败: %s', json_last_error_msg()));
        }

        if (!is_array($data)) {
            throw new GaNetApiException('API响应格式错误：响应不是有效的JSON数组');
        }

        // 检查API返回的response字段
        if (!isset($data['response'])) {
            throw new GaNetApiException('API响应格式错误：缺少response字段');
        }

        if (200 !== $data['response']) {
            $errorCode = $data['response'];
            $errorMessage = $this->getErrorMessage($errorCode);
            $errorCodeStr = is_scalar($errorCode) ? (string) $errorCode : 'unknown';
            throw new GaNetApiException(sprintf('API返回错误：%s (错误码: %s)', $errorMessage, $errorCodeStr));
        }

        return $data;
    }

    private function getErrorMessage(mixed $errorCode): string
    {
        $errorCodeStr = is_scalar($errorCode) ? (string) $errorCode : '';

        return match ($errorCodeStr) {
            '01' => '字段缺失',
            '02' => 'publisher id 错误',
            '03' => 'token缺失',
            '04' => '签名错误',
            '05' => '时间戳错误',
            default => '未知错误',
        };
    }
}
