<?php

namespace App\Services\YandexMaps\Dto;

use Carbon\CarbonImmutable;

final readonly class ReviewData
{
    public function __construct(
        public string $externalId,
        public ?string $authorName,
        public ?int $rating,
        public ?string $text,
        public ?CarbonImmutable $publishedAt,
        public array $raw = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            externalId: (string) ($data['reviewId'] ?? ''),
            authorName: $data['authorName'] ?? null,
            rating: isset($data['rating']) ? (int) $data['rating'] : null,
            text: $data['text'] ?? null,
            publishedAt: ! empty($data['publishedAt'])
                ? CarbonImmutable::parse($data['publishedAt'])
                : null,
            raw: is_array($data['raw'] ?? null) ? $data['raw'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'author_name' => $this->authorName,
            'rating' => $this->rating,
            'text' => $this->text,
            'published_at' => $this->publishedAt,
            'raw' => $this->raw ?: null,
        ];
    }
}