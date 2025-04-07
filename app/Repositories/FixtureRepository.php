<?php

namespace App\Repositories;

use App\Models\Fixture;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function getFixturesRecent(array $filters = [], int $perPage = 10, int $page = 1)
    {
        $query = $this->model->newQuery();

        if (isset($filters['teamId'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('home_team_id', $filters['teamId'])
                    ->orWhere('away_team_id', $filters['teamId']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->where('status', 'FINISHED')
            ->where('utc_date', '<=', now());


        $query->orderBy('utc_date', 'desc');

        $query->with(['homeTeam', 'awayTeam', 'competition']);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Lấy lịch sử đối đầu giữa hai đội bóng dựa trên ID trận đấu
     *
     * @param int $fixtureId ID của trận đấu
     * @param int $limit Số lượng trận đấu muốn lấy
     * @param int $page Số trang
     * @return array
     */
    public function getHeadToHeadFixturesByFixtureId(int $fixtureId, int $limit = 10, int $page = 1): array
    {
        // Lấy thông tin trận đấu hiện tại
        $currentFixture = $this->model->findOrFail($fixtureId);

        // Lấy ID của hai đội
        $team1Id = $currentFixture->home_team_id;
        $team2Id = $currentFixture->away_team_id;

        $query = $this->model->newQuery();

        // Lấy các trận đấu giữa hai đội
        $query->where(function ($q) use ($team1Id, $team2Id) {
            $q->where(function ($innerQ) use ($team1Id, $team2Id) {
                $innerQ->where('home_team_id', $team1Id)
                    ->where('away_team_id', $team2Id);
            })->orWhere(function ($innerQ) use ($team1Id, $team2Id) {
                $innerQ->where('home_team_id', $team2Id)
                    ->where('away_team_id', $team1Id);
            });
        });


        $query->where('status', 'FINISHED');

        $query->orderBy('utc_date', 'desc');

        $query->with(['homeTeam', 'awayTeam', 'competition']);

        $fixtures = $query->paginate($limit, ['*'], 'page', $page);

        $stats = [
            'team1' => [
                'id' => $team1Id,
                'name' => $currentFixture->homeTeam->name,
                'total_matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'home_wins' => 0,
                'home_draws' => 0,
                'home_losses' => 0,
                'home_goals_for' => 0,
                'home_goals_against' => 0,
                'away_wins' => 0,
                'away_draws' => 0,
                'away_losses' => 0,
                'away_goals_for' => 0,
                'away_goals_against' => 0,
            ],
            'team2' => [
                'id' => $team2Id,
                'name' => $currentFixture->awayTeam->name,
                'total_matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'home_wins' => 0,
                'home_draws' => 0,
                'home_losses' => 0,
                'home_goals_for' => 0,
                'home_goals_against' => 0,
                'away_wins' => 0,
                'away_draws' => 0,
                'away_losses' => 0,
                'away_goals_for' => 0,
                'away_goals_against' => 0,
            ],
        ];

        foreach ($fixtures->items() as $fixture) {
            $homeScore = $fixture->full_time_home_score ?? 0;
            $awayScore = $fixture->full_time_away_score ?? 0;


             $stats['team1']['total_matches']++;
            $stats['team2']['total_matches']++;

            // Cập nhật thống kê dựa trên kết quả trận đấu
            if ($fixture->home_team_id == $team1Id) {
                // Team 1 là đội chủ nhà
                $stats['team1']['home_goals_for'] += $homeScore;
                $stats['team1']['home_goals_against'] += $awayScore;
                $stats['team2']['away_goals_for'] += $awayScore;
                $stats['team2']['away_goals_against'] += $homeScore;

                if ($homeScore > $awayScore) {
                    $stats['team1']['wins']++;
                    $stats['team1']['home_wins']++;
                    $stats['team2']['losses']++;
                    $stats['team2']['away_losses']++;
                } elseif ($homeScore < $awayScore) {
                    $stats['team1']['losses']++;
                    $stats['team1']['home_losses']++;
                    $stats['team2']['wins']++;
                    $stats['team2']['away_wins']++;
                } else {
                    $stats['team1']['draws']++;
                    $stats['team1']['home_draws']++;
                    $stats['team2']['draws']++;
                    $stats['team2']['away_draws']++;
                }
            } else {
                // Team 1 là đội khách
                $stats['team1']['away_goals_for'] += $awayScore;
                $stats['team1']['away_goals_against'] += $homeScore;
                $stats['team2']['home_goals_for'] += $homeScore;
                $stats['team2']['home_goals_against'] += $awayScore;

                if ($awayScore > $homeScore) {
                    $stats['team1']['wins']++;
                    $stats['team1']['away_wins']++;
                    $stats['team2']['losses']++;
                    $stats['team2']['home_losses']++;
                } elseif ($awayScore < $homeScore) {
                    $stats['team1']['losses']++;
                    $stats['team1']['away_losses']++;
                    $stats['team2']['wins']++;
                    $stats['team2']['home_wins']++;
                } else {
                    $stats['team1']['draws']++;
                    $stats['team1']['away_draws']++;
                    $stats['team2']['draws']++;
                    $stats['team2']['home_draws']++;
                }
            }


            $stats['team1']['goals_for'] = $stats['team1']['home_goals_for'] + $stats['team1']['away_goals_for'];
            $stats['team1']['goals_against'] = $stats['team1']['home_goals_against'] + $stats['team1']['away_goals_against'];
            $stats['team2']['goals_for'] = $stats['team2']['home_goals_for'] + $stats['team2']['away_goals_for'];
            $stats['team2']['goals_against'] = $stats['team2']['home_goals_against'] + $stats['team2']['away_goals_against'];
        }

        return [
            'fixtures' => $fixtures,
            'stats' => $stats
        ];
    }
}
