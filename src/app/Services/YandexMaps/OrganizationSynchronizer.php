<?php

namespace App\Services\YandexMaps;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Models\Review;
use App\Services\YandexMaps\Dto\OrganizationData;
use App\Services\YandexMaps\Exceptions\LayoutChangedException;
use App\Services\YandexMaps\Exceptions\ParserException;
use Illuminate\Support\Facades\DB;

class OrganizationSynchronizer
{
    public function __construct(
        private readonly YandexMapsParser $parser,
    ) {
    }

    public function sync(Organization $organization): ParseRun
    {
        $run = ParseRun::create([
            'organization_id' => $organization->id,
            'status' => ParseStatus::Running->value,
            'started_at' => now(),
        ]);

        $organization->update([
            'parse_status' => ParseStatus::Running,
            'parse_error' => null,
        ]);

        try {
            $data = $this->parser->parse($organization->yandex_url);

            DB::transaction(function () use ($organization, $data) {
                $this->applyOrganization($organization, $data);
                $this->upsertReviews($organization, $data);
            });

            $organization->update([
                'parse_status' => ParseStatus::Ok,
                'parse_error' => null,
                'last_parsed_at' => now(),
            ]);

            $run->update([
                'status' => ParseStatus::Ok->value,
                'reviews_found' => count($data->reviews),
                'snapshot' => [
                    'rating' => $data->rating,
                    'ratings_count' => $data->ratingsCount,
                    'reviews_count' => $data->reviewsCount,
                    'collected' => count($data->reviews),
                ],
                'finished_at' => now(),
            ]);

            return $run->fresh();
        } catch (LayoutChangedException $e) {
            $this->markFailed($organization, $run, $e, ParseStatus::LayoutChanged);

            throw $e;
        } catch (ParserException $e) {
            $this->markFailed($organization, $run, $e, ParseStatus::Failed);

            throw $e;
        }
    }

    private function applyOrganization(Organization $organization, OrganizationData $data): void
    {
        $organization->update([
            'yandex_id' => $data->yandexId ?? $organization->yandex_id,
            'name' => $data->name ?? $organization->name,
            'address' => $data->address ?? $organization->address,
            'rating' => $data->rating,
            'ratings_count' => $data->ratingsCount,
            'reviews_count' => $data->reviewsCount,
        ]);
    }

    private function upsertReviews(Organization $organization, OrganizationData $data): void
    {
        $now = now();

        $rows = [];
        foreach ($data->reviews as $review) {
            if ($review->externalId === '') {
                continue;
            }

            $rows[] = [
                'organization_id' => $organization->id,
                'external_id' => $review->externalId,
                'author_name' => $review->authorName,
                'rating' => $review->rating,
                'text' => $review->text,
                'published_at' => $review->publishedAt,
                'raw' => $review->raw ? json_encode($review->raw, JSON_UNESCAPED_UNICODE) : null,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return;
        }

        Review::upsert(
            $rows,
            ['organization_id', 'external_id'],
            ['author_name', 'rating', 'text', 'published_at', 'raw', 'last_seen_at', 'updated_at']
        );
    }

    private function markFailed(
        Organization $organization,
        ParseRun $run,
        \Throwable $e,
        ParseStatus $status,
    ): void {
        $organization->update([
            'parse_status' => $status,
            'parse_error' => mb_substr($e->getMessage(), 0, 5000),
        ]);

        $run->update([
            'status' => $status->value,
            'error_message' => mb_substr($e->getMessage(), 0, 5000),
            'finished_at' => now(),
        ]);
    }
}