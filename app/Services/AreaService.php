<?php

namespace App\Services;

use App\Models\Area;
use App\Repositories\AreaRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AreaService
{
    private string $apiUrl;
    private string $apiToken;

    public function __construct(
        private AreaRepository $areaRepository
    ) {
        $this->apiToken = config('services.football_api.token');
        $this->apiUrl = config('services.football_api.url');
    }

    /**
     * Sync all areas from the API
     */
    public function syncAreas(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken
            ])->get("{$this->apiUrl}/areas/");

            if (!$response->successful()) {
                throw new \Exception("API request failed: {$response->status()}");
            }

            $areas = $response->json("areas");


            $stats = [
                'created' => 0,
                'updated' => 0,
                'failed' => 0
            ];

            foreach ($areas as $areaData) {
                DB::beginTransaction();
                try {
                    $area = $this->areaRepository->createOrUpdate([
                        'id' => $areaData['id'],
                        'name' => $areaData['name'],
                        'code' => $areaData['code'] ?? null,
                        'flag' => $areaData['flag'] ?? null,
                    ]);
                    \Log::info($area);
                    $wasRecentlyCreated = $area->wasRecentlyCreated;
                    $stats[$wasRecentlyCreated ? 'created' : 'updated']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    Log::error("Failed to sync area ID {$areaData['id']}: {$e->getMessage()}");
                }
            }

            DB::commit();

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            Log::error("Area sync failed: {$e->getMessage()}");
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get area by id
     * @param int $id
     * @return Area
     */
    public function getAreaById(int $id): Area
    {
        $area = $this->areaRepository->findById($id);
        if (!$area) {
            throw new ModelNotFoundException("Area not found");
        }
        return $area;
    }

    /**
     * Get all areas
     * @return array
     */
    public function getPaginatedAreas($filters, $perPage, $page)
    {
        $result = $this->areaRepository->getPaginatedAreas($filters, $perPage, $page);

        return [
            'total' => $result->total(),
            'current_page' => $result->currentPage(),
            'per_page' => $result->perPage(),
            'areas' => $result->items()
        ];
    }
}
