<?php

namespace App\Services\YandexMaps\Dto;

final readonly class OrganizationData
{
    /** @param ReviewData[] $reviews */
    public function __construct(
        public string $yandexUrl,
        public ?string $yandexId,
        public ?string $name,
        public ?string $address,
        public ?float $rating,
        public int $ratingsCount,
        public int $reviewsCount,
        public array $reviews = [],
        public array $meta = [],
    ) {
    }

    public static function fromArray(string $url, array $data): self
    {
        $org = $data['organization'] ?? [];
        $reviews = array_map(
            fn (array $r) => ReviewData::fromArray($r),
            $data['reviews'] ?? []
        );

        return new self(
            yandexUrl: $url,
            yandexId: self::extractYandexId($url),
            name: $org['name'] ?? null,
            address: $org['address'] ?? null,
            rating: isset($org['rating']) ? (float) $org['rating'] : null,
            ratingsCount: (int) ($org['ratingsCount'] ?? 0),
            reviewsCount: (int) ($org['reviewsCount'] ?? 0),
            reviews: $reviews,
            meta: $data['meta'] ?? [],
        );
    }

    public static function extractYandexId(string $url): ?string
    {
        if (preg_match('#/org/[^/]+/(\d+)#', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}