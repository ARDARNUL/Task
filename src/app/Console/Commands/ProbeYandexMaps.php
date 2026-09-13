<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ProbeYandexMaps extends Command
{
    protected $signature = 'yandex:probe {url}';
    protected $description = 'Разведка парсинга Яндекс.Карт';

    public function handle(): int
    {
        $url = $this->argument('url');

        if (! preg_match('#/org/([^/]+)/(\d+)#', $url, $m)) {
            $this->error('Не удалось извлечь seoname и businessId из URL');
            return self::FAILURE;
        }
        [, $seoname, $businessId] = $m;
        $this->info("seoname={$seoname} businessId={$businessId}");

        $cardUrl = "https://yandex.ru/maps/org/{$seoname}/{$businessId}/reviews/";

        // Полная хромовая шапка
        $chromeHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'sec-ch-ua' => '"Chromium";v="152", "Not?A_Brand";v="24", "Google Chrome";v="152"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ];

        $this->info("GET {$cardUrl}");
        $cardResponse = Http::withHeaders($chromeHeaders)
            ->withOptions(['http_errors' => false])
            ->get($cardUrl);

        $this->info("card HTTP {$cardResponse->status()}, " . strlen($cardResponse->body()) . " bytes");

        // Собираем cookies
        $cookies = [];
        $rawSetCookie = $cardResponse->headers()['Set-Cookie'] ?? [];
        foreach ($rawSetCookie as $line) {
            $pair = explode(';', $line, 2)[0];
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            if ($k !== '') $cookies[$k] = $v;
        }
        $this->line('cookies: ' . implode(', ', array_keys($cookies)));

        $html = $cardResponse->body();
        if (! preg_match('#<script type="application/json" class="state-view">(.*?)</script>#s', $html, $m)) {
            $this->error('Не нашли <script class="state-view">');
            return self::FAILURE;
        }
        $state = json_decode($m[1], true);
        $config = $state['config'] ?? [];

        $csrf = $config['csrfToken'] ?? null;
        $reqId = $config['requestId'] ?? null;
        $sessionId = $config['counters']['analytics']['sessionId'] ?? null;

        // Ищем s в html (может быть где угодно)
        $s = null;
        if (preg_match('#"s":"(\d+)"#', $html, $mm)) $s = $mm[1];
        $this->line('csrfToken: ' . ($csrf ?? 'NULL'));
        $this->line('s found: ' . ($s ?? 'NULL'));

        if (! $csrf) {
            $this->error('csrfToken не найден');
            return self::FAILURE;
        }

        // API-запрос с полной хромовой шапкой + все cookies
        $apiUrl = 'https://yandex.ru/maps/api/business/fetchReviews';
        $apiParams = [
            'ajax' => 1,
            'businessId' => $businessId,
            'csrfToken' => $csrf,
            'locale' => 'ru_RU',
            'page' => 1,
            'pageSize' => 50,
            'ranking' => 'by_relevance_org',
            'reqId' => $reqId,
            'sessionId' => $sessionId,
        ];
        if ($s) $apiParams['s'] = $s;

        $apiHeaders = [
            'User-Agent' => $chromeHeaders['User-Agent'],
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => $cardUrl,
            'sec-ch-ua' => $chromeHeaders['sec-ch-ua'],
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $this->info('GET fetchReviews (with full chrome headers + cookies)');

        $apiHttp = Http::withHeaders($apiHeaders)->withOptions(['http_errors' => false]);
        if ($cookies) {
            $cookieHeader = collect($cookies)->map(fn($v, $k) => "{$k}={$v}")->implode('; ');
            $apiHttp = $apiHttp->withHeaders(['Cookie' => $cookieHeader]);
        }

        $r = $apiHttp->get($apiUrl, $apiParams);
        $body = $r->body();
        $this->line("HTTP {$r->status()}, " . strlen($body) . " bytes");

        $json = $r->json();
        if ($json) {
            $this->line('reviews count: ' . count($json['data']['reviews'] ?? []));
            if (! empty($json['data']['params'])) {
                $this->line('params: ' . json_encode($json['data']['params'], JSON_UNESCAPED_UNICODE));
            }
            if (empty($json['data']['reviews'])) {
                $this->line('body: ' . substr($body, 0, 300));
            } else {
                $this->line('first author: ' . ($json['data']['reviews'][0]['author']['name'] ?? '?'));
            }
        } else {
            $this->line('body: ' . substr($body, 0, 300));
        }

        return self::SUCCESS;
    }
}