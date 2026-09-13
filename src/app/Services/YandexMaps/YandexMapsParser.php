<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Dto\OrganizationData;
use App\Services\YandexMaps\Exceptions\LayoutChangedException;
use App\Services\YandexMaps\Exceptions\ParserException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class YandexMapsParser
{
    private string $endpoint;
    private int $timeout;

    public function __construct()
    {
        $this->endpoint = rtrim((string) config('services.yandex_maps.parser_url', 'http://browser:3000'), '/');
        $this->timeout = (int) config('services.yandex_maps.timeout', 300);
    }

    public function parse(string $url): OrganizationData
    {
        if (! $this->isValidYandexUrl($url)) {
            throw new ParserException("Некорректная ссылка Яндекс.Карт: {$url}");
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint . '/parse', ['url' => $url]);
        } catch (Throwable $e) {
            Log::error('YandexMapsParser: HTTP failure', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new ParserException("Парсер недоступен: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            Log::error('YandexMapsParser: non-2xx', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            throw new ParserException("Парсер вернул статус {$response->status()}");
        }

        $json = $response->json();
        if (! is_array($json) || ($json['ok'] ?? false) !== true) {
            throw new ParserException('Парсер вернул некорректный JSON: ' . substr($response->body(), 0, 300));
        }

        $reviews = $json['reviews'] ?? [];
        $org = $json['organization'] ?? [];
        $hasOrg = ! empty($org['name']) || ! empty($org['rating']);
        $hasReviews = is_array($reviews) && count($reviews) > 0;

        if (! $hasOrg && ! $hasReviews) {
            throw new LayoutChangedException(
                'Парсер получил пустой ответ — вероятно, Яндекс изменил разметку карточки.'
            );
        }

        return OrganizationData::fromArray($url, $json);
    }

    public function isValidYandexUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://yandex\.ru/maps/org/[^/]+/\d+#', $url);
    }
}