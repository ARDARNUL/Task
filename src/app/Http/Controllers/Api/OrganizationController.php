<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\ReviewResource;
use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $organizations = Organization::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $user = $request->user();
        $url = $request->validated('yandex_url');

        $organization = Organization::updateOrCreate(
            ['user_id' => $user->id, 'yandex_url' => $url],
            [
                'user_id' => $user->id,
                'yandex_url' => $url,
                'parse_status' => ParseStatus::Pending,
                'parse_error' => null,
            ]
        );

        ParseOrganizationJob::dispatch($organization);

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(202);
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizeOrganization($request, $organization);

        $reviews = $organization->reviews()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(50);

        return response()->json([
            'organization' => new OrganizationResource($organization),
            'reviews' => ReviewResource::collection($reviews->items()),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    public function destroy(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizeOrganization($request, $organization);

        $organization->delete();

        return response()->json(['message' => 'Организация удалена.'], 200);
    }

    public function sync(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizeOrganization($request, $organization);

        $organization->update([
            'parse_status' => ParseStatus::Pending,
            'parse_error' => null,
        ]);

        ParseOrganizationJob::dispatch($organization);

        return response()->json([
            'message' => 'Парсинг запущен.',
            'organization' => new OrganizationResource($organization->fresh()),
        ], 202);
    }

    private function authorizeOrganization(Request $request, Organization $organization): void
    {
        if ($organization->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}