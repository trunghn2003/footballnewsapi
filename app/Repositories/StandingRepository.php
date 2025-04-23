<?php

namespace App\Repositories;

use App\Models\Standing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StandingRepository
{
    protected $model;

    public function __construct(Standing $model)
    {
        $this->model = $model;
    }

    public function storeStandings($competitionId, $seasonId, $matchday, $standingsData)
    {
        DB::beginTransaction();
        try {
            // Xóa dữ liệu cũ của competition, season và matchday này
            $this->model->where('competition_id', $competitionId)
                ->where('season_id', $seasonId)
                ->where('matchday', $matchday)
                ->delete();

            foreach ($standingsData as $standing) {
                foreach ($standing['table'] as $teamStanding) {
                    $this->model->create([
                        'competition_id' => $competitionId,
                        'season_id' => $seasonId,
                        'matchday' => $matchday,
                        'stage' => $standing['stage'],
                        'type' => $standing['type'],
                        'group' => $standing['group'],
                        'team_id' => $teamStanding['team']['id'],
                        'position' => $teamStanding['position'],
                        'played_games' => $teamStanding['playedGames'],
                        'form' => $teamStanding['form'],
                        'won' => $teamStanding['won'],
                        'draw' => $teamStanding['draw'],
                        'lost' => $teamStanding['lost'],
                        'points' => $teamStanding['points'],
                        'goals_for' => $teamStanding['goalsFor'],
                        'goals_against' => $teamStanding['goalsAgainst'],
                        'goal_difference' => $teamStanding['goalDifference']
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing standings: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteByMatchday($competitionId, $seasonId, $matchday)
    {
        try {
            return $this->model->where('competition_id', $competitionId)
                ->where('season_id', $seasonId)
                ->where('matchday', $matchday)
                ->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting standings by matchday: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            return $this->model->create($data);
        } catch (\Exception $e) {
            Log::error('Error creating standing: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getStandingsByCompetitionAndSeason($competitionId, $seasonId, $matchday, $type, $teamID = null)
    {
        return $this->model
            ->with(['team'])
            ->where('competition_id', $competitionId)
            ->where('season_id', $seasonId)
            ->where('matchday', $matchday)
            ->where('type', $type)
            ->when(!is_null($teamID), function ($query) use ($teamID) {
                return $query->where('team_id', $teamID);
            })
            ->orderBy('matchday', 'desc')
            ->orderBy('type')
            ->orderBy('position')
            ->get();
    }

    public function getStandingsByMatchday($competitionId, $seasonId, $matchday)
    {
        return $this->model
            ->with(['team', 'competition', 'season'])
            ->where('competition_id', $competitionId)
            ->where('season_id', $seasonId)
            ->where('matchday', $matchday)
            ->orderBy('type')
            ->orderBy('position')
            ->get();
    }

    public function getStandingsByType($competitionId, $seasonId, $matchday, $type)
    {
        return $this->model
            ->with(['team', 'competition', 'season'])
            ->where('competition_id', $competitionId)
            ->where('season_id', $seasonId)
            ->where('matchday', $matchday)
            ->where('type', $type)
            ->orderBy('position')
            ->get();
    }
}
