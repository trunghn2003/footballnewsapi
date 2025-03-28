<?php

namespace App\Services;

use App\Models\Competition;
use App\Repositories\AreaRepository;
use App\Repositories\CompetitionRepository;
use App\Repositories\SeasonRepository;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeasonService
{
    private string $apiUrlFootball;
    private string $apiToken;
    private SeasonRepository $seasonRepository;
    

    public function __construct(SeasonRepository $seasonRepository)
    {
        $this->apiUrlFootball = env('API_FOOTBALL_URL');
        $this->apiToken = env('API_FOOTBALL_TOKEN');
        $this->seasonRepository = $seasonRepository;
    }

    /**
     * Sync all competitions from the API
     */
    public function syncCompetitionsSeasons(): array
    {
        try {
            $names = [
                'PL',
                'CL',
                'FL1',
                // 'WC',
                'BL1',
                'SA',
                'PD',
             ];
            foreach ($names as $name) {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken
                ])->get("{$this->apiUrlFootball}/competitions/" . $name);
            
                if (!$response->successful()) {
                    throw new \Exception("API request failed: {$response->status()}");
                }

                $data = $response->json()['seasons'];
                $competitionId = $response->json()['id'];
                
                DB::beginTransaction();

                if (isset($data) && is_array($data) && $competitionId) {
                    foreach ($data as $seasonData) {
                        $this->seasonRepository->syncSeason($seasonData, $competitionId);
                    }
                }
            
                DB::commit();
            }

            return [
                'success' => true   
            ];
        } catch (\Exception $e) {
            Log::error("Competition sync failed: {$e->getMessage()}");
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get competition details by ID
     */
    public function getCompetitionDetails(int $competitionId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken
            ])->get("{$this->apiUrlFootball}/competitions/{$competitionId}");

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch competition details");
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Failed to get competition details: {$e->getMessage()}");
            return null;
        }
    }
}
