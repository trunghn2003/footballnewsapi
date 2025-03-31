<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\User;
use App\Repositories\CompetitionRepository;
use App\Repositories\PersonRepository;
use App\Repositories\TeamRepository;
use App\Repositories\PlayerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
class TeamService
{
    private $teamRepository;
    private $personRepository;
    private $competitionRepository;
    private string $apiUrl;
    private string $apiToken;
    public function __construct(
        TeamRepository $teamRepository,
        CompetitionRepository $competitionRepository,
        PersonRepository $personRepository
    ) {
        $this->teamRepository = $teamRepository;
        $this->competitionRepository = $competitionRepository;
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
                'PL' => 2021,
                'CL' => 2001,
                'FL1' => 2015,
                // 'WC',
                'BL1' => 2002,
                // 'BL2',
                'SA' => 2019,
                'PD' => 2014,
             ];
        try {
            foreach ($names as $name =>$id1) {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken
                ])->get("{$this->apiUrl}/competitions/{$name}/teams");

                if (!$response->successful()) {
                    throw new \Exception("API request failed: {$response->status()}");
                }
                $data = $response->json();
                // dd($data);

                if (empty($data['teams'])) {
                    return false;
                }
                // dump($id1);

                DB::transaction(function () use ($data, $id1) {
                    $competition = $this->competitionRepository->findById($id1);
                    $currentSeason = $competition->currentSeason;
                    // dd($currentSeason);
                    foreach ($data['teams'] as $teamData) {
                        $team = $this->teamRepository->updateOrCreateTeam($teamData);
                        DB::table('team_competition_season')->updateOrInsert(
                            [
                                'team_id' => $team->id,
                                'competition_id' => $competition->id,
                                'season_id' => $currentSeason->id
                            ],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                        \Log::info('Team: ' . $team->name . ' ' .$competition->id. ' '. $currentSeason->id . ' synced successfully.');
                        // foreach ($teamData['squad'] as $playerData) {
                        //     $this->personRepository->syncPerson($playerData, $team->id);
                        // }
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

    public function addFavoriteTeam(int $teamId): bool
    {
        $user = User::where('id', auth()->user()->id)->first();
        if (!$user) {
            return false;
        }
        // dd($user);
        $favoriteTeams = $user->favourite_teams;
        if (!is_array($favoriteTeams)) {
            $favoriteTeams = json_decode($favoriteTeams, true) ?? [];
        }
        // dd($favoriteTeams);
        if (!in_array($teamId, $favoriteTeams)) {
            $favoriteTeams[] = $teamId;
            $user->favourite_teams = $favoriteTeams;
            $user->save();
        }
        return true;
    }

    public function removeFavoriteTeam(int $teamId): bool
    {
        $user = User::where('id', auth()->user()->id)->first();
        if (!$user) {
            return false;
        }
        $favoriteTeams = $user->favourite_teams;
        if (!is_array($favoriteTeams)) {
            $favoriteTeams = json_decode($favoriteTeams, true) ?? [];
        }
        if (in_array($teamId, $favoriteTeams)) {
            $key = array_search($teamId, $favoriteTeams);
            unset($favoriteTeams[$key]);
            $user->favourite_teams = $favoriteTeams;
            $user->save();
        }
        return true;
    }
    public function getTeams(array $filters, int $perPage, int $page): array
    {
        $data =  $this->teamRepository->getAll($filters, $perPage, $page);
        if (!isset($data) || $data->isEmpty()) {
            return [
                'teams' => [],
                'meta' => [
                    'current_page' => 0,
                    'per_page' => 0,
                ],
            ];
        }
        return [
            'teams' => $data->items() ,
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
            ],
        ];
    }
}
