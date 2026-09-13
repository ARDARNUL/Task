<?php

namespace App\Jobs;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Services\YandexMaps\Exceptions\LayoutChangedException;
use App\Services\YandexMaps\Exceptions\ParserException;
use App\Services\YandexMaps\OrganizationSynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ParseOrganizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public int $timeout = 600;

    public function __construct(
        public readonly Organization $organization,
    ) {
    }

    public function handle(OrganizationSynchronizer $synchronizer): void
    {
        Log::info('ParseOrganizationJob: start', [
            'organization_id' => $this->organization->id,
            'url' => $this->organization->yandex_url,
            'attempt' => $this->attempts(),
        ]);

        $synchronizer->sync($this->organization);

        Log::info('ParseOrganizationJob: done', [
            'organization_id' => $this->organization->id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ParseOrganizationJob: failed permanently', [
            'organization_id' => $this->organization->id,
            'error' => $exception->getMessage(),
        ]);

        $status = $exception instanceof LayoutChangedException
            ? ParseStatus::LayoutChanged
            : ParseStatus::Failed;

        $this->organization->update([
            'parse_status' => $status,
            'parse_error' => mb_substr($exception->getMessage(), 0, 5000),
        ]);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    public function shouldRetry(Throwable $exception): bool
    {
        return ! $exception instanceof LayoutChangedException;
    }
}