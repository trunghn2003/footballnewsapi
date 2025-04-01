<?php

namespace App\Repositories;

use App\Models\Fixture;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class FixtureRepository
{
    protected $model;

    public function __construct(Fixture $fixture)
    {
        $this->model = $fixture;
    }

    public function createOrUpdate(array $data): Fixture
    {
        $full_time_home_score   = null;
        $full_time_away_score = null;
        $half_time_home_score = null;
        $half_time_away_score = null;
        $extra_time_home_score = null;
        $extra_time_away_score = null;
        $penalties_home_score = null;
        $penalties_away_score = null;
        $winner = null;
        $duration = null;

        if (isset($data['score'])) {
            if (isset($data['score']['fullTime'])  && isset($data['score']['halfTime'])) {
                $full_time_home_score   = $data['score']['fullTime']['home'] ?? null;
                $full_time_away_score   = $data['score']['fullTime']['away'] ?? null;
                $half_time_home_score   = $data['score']['halfTime']['home'] ?? null;
                $half_time_away_score   = $data['score']['halfTime']['away'];
            }
            if (isset($data['score']['extraTime'])) {
                $extra_time_home_score   = $data['score']['extraTime']['home'];
                $extra_time_away_score   = $data['score']['extraTime']['away'];
            }
            if (isset($data['score']['penalties'])) {
                $penalties_home_score   = $data['score']['penalties']['home'];
                $penalties_away_score   = $data['score']['penalties']['away'];
            }
            if (isset($data['score']['winner'])) {
                $winner = $data['score']['winner'] ?? null;
            }
            if (isset($data['score']['duration'])) {
                $duration = $data['score']['duration'] ?? null;
            }
        }

        return Fixture::updateOrCreate(
            ['id' => $data['id']],
            [
                'utc_date' => $data['utcDate'],
                'status' => $data['status'],
                'matchday' => $data['matchday'],
                'stage' => $data['stage'],
                'season_id' => $data['season']['id'],
                'home_team_id' => $data['homeTeam']['id'],
                'away_team_id' => $data['awayTeam']['id'],
                'full_time_home_score' => $full_time_home_score,
                'full_time_away_score' => $full_time_away_score,
                'half_time_home_score' => $half_time_home_score,
                'half_time_away_score' => $half_time_away_score,
                'penalties_home_score' => $penalties_home_score,
                'penalties_away_score' => $penalties_away_score,
                'extra_time_home_score' => $extra_time_home_score,
                'extra_time_away_score' => $extra_time_away_score,
                'winner' => $winner,
                'duration' => $duration,
                'competition_id' => $data['competition']['id'],
                'last_updated' => now(),
            ]
        );
    }

    public function findById(int $id): ?Fixture
    {
        try {
            $result = $this->model->findOrFail($id);
            return $result;
        } catch (\Exception $e) {
            Log::error("Fixture not found: {$e->getMessage()}");
            throw new ModelNotFoundException($e->getMessage());
        }
    }

    public function getFixtures(array $filters = [], int $perPage = 10, int $page = 1, $flag = false)
    {
        $query = $this->model->query();
        if (isset($filters['competition'])) {
            $query->where('competition_id', $filters['competition']);
        }
        if (isset($filters['competition_id'])) {
            $query->where('competition_id', $filters['competition_id']);
        }

        if (isset($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        if (isset($filters['dateFrom'])) {
            // dd(1);
            $query->where('utc_date', '>=', $filters['dateFrom']);
        }

        if (isset($filters['dateTo'])) {
            $query->where('utc_date', '<=', $filters['dateTo']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['teamName'])) {
            $query->whereHas('homeTeam', function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['teamName'] . '%');
            })
                ->orWhereHas('awayTeam', function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . $filters['teamName'] . '%');
                });
        }
        if (isset($filters['teamId'])) {
            $query->where('home_team_id', $filters['teamId'])
                ->orWhere('away_team_id', $filters['teamId']);
        }
        if (!$flag)
            $query->where('utc_date', '>', now());


        return $query
            ->with(['homeTeam', 'awayTeam', 'homeLineup.players.players', 'awayLineup.player.players'])
            ->orderBy('utc_date', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
