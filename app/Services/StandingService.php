<?php

namespace App\Services;

use App\Repositories\CompetitionRepository;
use App\Repositories\StandingRepository;
use App\Repositories\TeamRepository;
use Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StandingService
{
    protected $standingRepository;
    protected $apiToken;
    protected $apiUrlFootball;
    protected $competitionRepository;
    protected $teamRepository;

    public function __construct(StandingRepository $standingRepository, CompetitionRepository $competitionRepository, TeamRepository $teamRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->standingRepository = $standingRepository;
        $this->apiToken = env('API_FOOTBALL_TOKEN');
        $this->apiUrlFootball = env('API_FOOTBALL_URL');
        $this->competitionRepository = $competitionRepository;
    }

    public function storeStandings($competitionId, $seasonId, $matchday, $standingsData)
    {
        try {
            return $this->standingRepository->storeStandings($competitionId, $seasonId, $matchday, $standingsData);
        } catch (\Exception $e) {
            Log::error('Error in StandingService storeStandings: ' . $e->getMessage());
            throw $e;
        }
    }

    public function storeStandingsFromApi()
    {
        DB::beginTransaction();
        try {
            $names = [
                'PD',
                'FL1',
                'BL1',
                'SA',
                'PL',
            ];

            foreach ($names as $name) {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken,
                ])->get("{$this->apiUrlFootball}/competitions/{$name}/standings?season=2024");

                if (!$response->successful()) {
                    Log::error("API request failed for competition {$name}: {$response->status()} - {$response->body()}");
                    throw new \Exception("API request failed for competition {$name}: {$response->status()}");
                }

                $data = $response->json();

                if (empty($data['competition']['id'])) {
                    Log::error("Competition ID missing in API response for {$name}. Response data: " . json_encode($data));
                    throw new \Exception("Competition ID missing in API response for {$name}");
                }

                $competitionId = $data['competition']['id'];
                $competition = $this->competitionRepository->getById($competitionId);

                if (!$competition) {
                    Log::error("Competition not found in database with ID: {$competitionId}");
                    throw new \Exception("Competition not found in database with ID: {$competitionId}");
                }

                if (empty($competition->currentSeason->id)) {
                    Log::error("Season ID missing for competition {$name}. Competition data: " . json_encode($competition));
                    throw new \Exception("Season ID missing for competition {$name}");
                }

                $seasonId = $competition->currentSeason->id;
                $currentSeason = $competition->currentSeason;
                $currentSeason->current_matchday = $data['season']['currentMatchday'];
                $currentSeason->save();

                if (empty($data['season']['currentMatchday'])) {
                    Log::error("Matchday missing in API response for {$name}. Response data: " . json_encode($data));
                    throw new \Exception("Matchday missing in API response for {$name}");
                }

                $matchday = $data['season']['currentMatchday'];
                $this->standingRepository->deleteByMatchday($competitionId, $seasonId, $matchday);

                if (empty($data['standings'])) {
                    Log::warning("No standings data found for competition {$name}.");
                    continue;
                }

                foreach ($data['standings'] as $standing) {
                    if (empty($standing['table'])) {
                        Log::warning("No table data found for standing in competition {$name}. Standing data: " . json_encode($standing));
                        continue;
                    }

                    foreach ($standing['table'] as $teamStanding) {
                        if (empty($teamStanding['team']['id'])) {
                            Log::error("Team ID missing in team standing data for {$name}. Team standing data: " . json_encode($teamStanding));
                            throw new \Exception("Team ID missing in team standing data for {$name}");
                        }

                        $standingData = [
                            'competition_id' => $competitionId,
                            'season_id' => $seasonId,
                            'matchday' => $matchday,
                            'team_id' => $teamStanding['team']['id'],
                            'stage' => $standing['stage'] ?? null,
                            'type' => $standing['type'] ?? null,
                            'group' => $standing['group'] ?? null,
                            'position' => $teamStanding['position'],
                            'played_games' => $teamStanding['playedGames'],
                            'form' => $teamStanding['form'] ?? null,
                            'won' => $teamStanding['won'],
                            'draw' => $teamStanding['draw'],
                            'lost' => $teamStanding['lost'],
                            'points' => $teamStanding['points'],
                            'goals_for' => $teamStanding['goalsFor'],
                            'goals_against' => $teamStanding['goalsAgainst'],
                            'goal_difference' => $teamStanding['goalDifference'],
                        ];

                        $this->standingRepository->create($standingData);
                        Log::info('Standing created: ' . $teamStanding['team']['name'] . ' for competition ' . $name);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing standings: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            throw $e;
        }
    }


    public function getStandingsByCompetitionAndSeason($request)
    {
        $competitionId = $request->competition_id;
        $seasonId = $request->season_id;
        $teamID = $request->team_id ?? null;
        if (isset($request->teamName)) {
            try {
                $teamID = $this->teamRepository->findByName($request->teamName)->id;
            } catch (\Exception $e) {
                return null;
            }
        }
        if (isset($request->name)) {
            try {
                $competitionId = $this->competitionRepository->findByName($request->name)->id ;
            } catch (\Exception $e) {
                return null;
            }
        }
        if (!isset($seasonId)) {
            $seasonId = $this->competitionRepository->findById($competitionId)->currentSeason->id;
        }

        $matchday = $request->matchday;
        if (!isset($matchday)) {
            $matchday = $this->competitionRepository->findById($competitionId)->currentSeason->current_matchday;
        }
        $type = $request->type ?? 'TOTAL';
        try {
            return $this->standingRepository->getStandingsByCompetitionAndSeason($competitionId, $seasonId, $matchday, $type, $teamID);
        } catch (\Exception $e) {
            Log::error('Error in StandingService getStandingsByCompetitionAndSeason: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getStandingsByMatchday($competitionId, $seasonId, $matchday)
    {
        try {
            $result =   $this->standingRepository->getStandingsByMatchday($competitionId, $seasonId, $matchday);
        } catch (\Exception $e) {
            Log::error('Error in StandingService getStandingsByMatchday: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getStandingsByType($competitionId, $seasonId, $matchday, $type)
    {
        try {
            return $this->standingRepository->getStandingsByType($competitionId, $seasonId, $matchday, $type);
        } catch (\Exception $e) {
            Log::error('Error in StandingService getStandingsByType: ' . $e->getMessage());
            throw $e;
        }
    }
}
