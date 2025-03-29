<?php

namespace App\Services;

use App\Repositories\PersonRepository;
use App\Repositories\TeamRepository;
use App\Repositories\PlayerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
class TeamService
{
    private $teamRepository;
    private $personRepository;
    private string $apiUrl;
    private string $apiToken;
    public function __construct(
        TeamRepository $teamRepository,

        PersonRepository $personRepository
    ) {
        $this->teamRepository = $teamRepository;

        $this->personRepository = $personRepository;
        $this->apiUrl = env('API_FOOTBALL_URL');
        $this->apiToken = env('API_FOOTBALL_TOKEN');
    }

     /**
     * Sync Premier League teams and players.
     *
     * @return bool
     */
    public function syncTeamsAndPlayers(): bool
    {
        $names = [
                // 'PL',
                'CL',
                'FL1',
                // 'WC',
                'BL1',
                // 'BL2',
                'SA',
                'PD',
             ];
        try {
            foreach ($names as $name) {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken
                ])->get("{$this->apiUrl}/competitions/{$name}/teams");

                if (!$response->successful()) {
                    throw new \Exception("API request failed: {$response->status()}");
                }
                $data = $response->json();

                if (empty($data['teams'])) {
                    return false;
                }

                DB::transaction(function () use ($data) {
                    foreach ($data['teams'] as $teamData) {

                        $team = $this->teamRepository->updateOrCreateTeam($teamData);

                        foreach ($teamData['squad'] as $playerData) {
                            $this->personRepository->syncPerson($playerData, $team->id);
                        }
                    }
                });
                DB::commit();
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('League  sync failed: ' . $e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    public function getTeamById(int $id)
    {
        return $this->teamRepository->findById($id);
    }
}