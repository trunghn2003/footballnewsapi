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
    protected $teamRepository;

    public function __construct(Fixture $fixture, TeamRepository $teamRepository)
    {
        $this->model = $fixture;
        $this->teamRepository = $teamRepository;
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

        // Áp dụng bộ lọc competition
        if (isset($filters['competition'])) {
            $query->where('competition_id', $filters['competition']);
        }

        if (isset($filters['competition_id'])) {
            $query->where('competition_id', $filters['competition_id']);
        }

        // Lọc theo danh sách ID
        if (isset($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        // Lọc theo khoảng thời gian
        if (isset($filters['dateFrom'])) {
            $query->where('utc_date', '>=', $filters['dateFrom']);
        }

        if (isset($filters['dateTo'])) {
            $query->where('utc_date', '<=', $filters['dateTo']);
        }

        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            if($filters['status'] == 'SCHEDULED')
            {
                $query->where('status', '!=', 'FINISHED');
            }
            else
            {
                $query->where('status', $filters['status']);
            }
            // $query->where('status', $filters['status']);
        }

        // Lọc theo tên đội bóng
        if (isset($filters['teamName'])) {
            $query->where(function($q) use ($filters) {
                $q->whereHas('homeTeam', function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . $filters['teamName'] . '%');
                })
                ->orWhereHas('awayTeam', function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . $filters['teamName'] . '%');
                });
            });
        }

        // Lọc theo ID đội bóng
        if (isset($filters['teamId'])) {
            $query->where(function($q) use ($filters) {
                $q->where('home_team_id', $filters['teamId'])
                  ->orWhere('away_team_id', $filters['teamId']);
            });
        }

        $query->orderBy('utc_date', 'asc');

        // Lấy kết quả với các mối quan hệ và sắp xếp
        return $query
            ->with(['homeTeam', 'awayTeam', 'homeLineup.players.players', 'awayLineup.player.players'])
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

    public function createOrUpdatev2(array $data, $season_id, $competition_id)
    {
        // dd($data);
        $full_time_home_score = null;
        $full_time_away_score = null;
        $half_time_home_score = null;
        $half_time_away_score = null;
        $extra_time_home_score = null;
        $extra_time_away_score = null;
        $penalties_home_score = null;
        $penalties_away_score = null;
        $winner = null;
        $duration = null;

        // Extract score data
        if (isset($data['score'])) {
            if (isset($data['score']['fulltime'])) {
                $full_time_home_score = $data['score']['fulltime']['home'] ?? null;
                $full_time_away_score = $data['score']['fulltime']['away'] ?? null;
            }
            if (isset($data['score']['halftime'])) {
                $half_time_home_score = $data['score']['halftime']['home'] ?? null;
                $half_time_away_score = $data['score']['halftime']['away'] ?? null;
            }
            if (isset($data['score']['extratime'])) {
                $extra_time_home_score = $data['score']['extratime']['home'] ?? null;
                $extra_time_away_score = $data['score']['extratime']['away'] ?? null;
            }
            if (isset($data['score']['penalty'])) {
                $penalties_home_score = $data['score']['penalty']['home'] ?? null;
                $penalties_away_score = $data['score']['penalty']['away'] ?? null;
            }
        }

        // Determine winner
        if (isset($data['teams']['home']['winner'])) {
            if ($data['teams']['home']['winner'] === true) {
                $winner = 'HOME_TEAM';
            } elseif ($data['teams']['away']['winner'] === true) {
                $winner = 'AWAY_TEAM';
            } else {
                $winner = 'DRAW';
            }
        }

        // Extract the matchday from round (if available)
        $matchday = null;
        if (isset($data['league']['round']) && preg_match('/Regular Season - (\d+)/', $data['league']['round'], $matches)) {
            $matchday = (int) $matches[1];
        }

        // Map the round/stage string to standardized stage values
        $stage = $this->mapStage($data['league']['round'] ?? null);


        //        $season_id =$season_id;
        $country = $data['league']['country'] ?? null;
        if ($data['league']['id'] == 41) {
            $country = 'France';
        }
        $homeTeam = $this->teamRepository->findByName($data['teams']['home']['name']);
        // Create or update home team using the dedicated function
        if (!$homeTeam) {
            return;
            $homeTeam = $this->teamRepository->updateOrCreateTeam([
                'id' => $this->teamRepository->generateNewId(),
                'name' => $data['teams']['home']['name'],
                'logo' => $data['teams']['home']['logo'] ?? null,
                'country' => $country,

                'last_synced' => now(),
                'area_id' => $data['area']['id'] ?? 2267,
                'last_updated' => now()
            ]);
            \Log::info('Home team created: ' . $data['teams']['home']['name']);
        }
        $awayTeam = $this->teamRepository->findByName($data['teams']['away']['name']);
        if (!$awayTeam) {
            return;
            // Create or update away team using the dedicated function
            $awayTeam = $this->teamRepository->updateOrCreateTeam([
                'id' => $this->teamRepository->generateNewId(),
                'name' => $data['teams']['away']['name'],
                'logo' => $data['teams']['away']['logo'] ?? null,
                'country' => $country,

                'last_synced' => now(),
                'area_id' => $data['area']['id'] ?? 2267,
                'last_updated' => now()
            ]);
            \Log::info('Away team created: ' . $data['teams']['away']['name']);
        }


        $fixture = Fixture::updateOrCreate(
            ['id' => $this->generateNewId()],
            [
                'utc_date' => $data['fixture']['date'],
                'status' => 'FINISHED',
                'matchday' => $matchday,
                'stage' => $stage,
                'season_id' => $season_id,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'full_time_home_score' => $full_time_home_score,
                'full_time_away_score' => $full_time_away_score,
                'half_time_home_score' => $half_time_home_score,
                'half_time_away_score' => $half_time_away_score,
                'penalties_home_score' => $penalties_home_score,
                'penalties_away_score' => $penalties_away_score,
                'extra_time_home_score' => $extra_time_home_score,
                'extra_time_away_score' => $extra_time_away_score,
                'winner' => $winner,
                'duration' => isset($data['fixture']['status']['elapsed']) ? 'REGULAR' : null,
                'competition_id' => $competition_id,
                //                'referee' => $data['fixture']['referee'] ?? null,
                'venue' => $data['fixture']['venue']['name'] ?? null,
                //                'venue_city' => $data['fixture']['venue']['city'] ?? null,
                'last_updated' => now(),
            ]
        );
        \Log::info('Fixture created: ' . $fixture->id);
        return $fixture;
    }

    /**
     * Map API stage/round string to standardized stage values
     *
     * @param string|null $roundString
     * @return string|null
     */
    private function mapStage(?string $roundString): ?string
    {
        if (!$roundString) {
            return null;
        }

        $lowercaseRound = strtolower($roundString);

        if (strpos($lowercaseRound, 'regular season') !== false) {
            return 'REGULAR_SEASON';
        }

        if (
            strpos($lowercaseRound, 'league stage') !== false ||
            strpos($lowercaseRound, 'group') !== false
        ) {
            return 'LEAGUE_STAGE';
        }

        if (strpos($lowercaseRound, 'playoff') !== false) {
            return 'PLAYOFFS';
        }

        if (
            strpos($lowercaseRound, 'round of 16') !== false ||
            strpos($lowercaseRound, 'last 16') !== false ||
            strpos($lowercaseRound, '1/8') !== false
        ) {
            return 'LAST_16';
        }

        if (
            strpos($lowercaseRound, 'quarter') !== false ||
            strpos($lowercaseRound, 'quarter-final') !== false ||
            strpos($lowercaseRound, '1/4') !== false
        ) {
            return 'QUARTER_FINALS';
        }

        if (
            strpos($lowercaseRound, 'semi') !== false ||
            strpos($lowercaseRound, 'semi-final') !== false ||
            strpos($lowercaseRound, '1/2') !== false
        ) {
            return 'SEMI_FINALS';
        }

        if (strpos($lowercaseRound, 'final') !== false) {
            return 'FINAL';
        }

        // If no match, return the original string
        return $roundString;
    }

    public function generateNewId()
    {
        $maxId = Fixture::max('id');
        return $maxId + 1;
    }

    public function findByTla($tlaHome, $tlaAway, $competitionId)
    {
        $query = $this->model->newQuery();
        $query->where('competition_id', $competitionId)
            ->where(function ($q) use ($tlaHome, $tlaAway) {
                $q->whereHas('homeTeam', function ($query) use ($tlaHome) {
                    $query->where('tla', $tlaHome);
                });
                $q->WhereHas('awayTeam', function ($query) use ($tlaAway) {
                        $query->where('tla', $tlaAway);
                    });
            });
            $query->where('status', 'FINISHED')
            ->where('utc_date', '<=', now());

        return $query->orderBy('utc_date', 'desc')
            // ->with(['homeTeam', 'awayTeam'])
            ->first();
    }

    public function findByTLAOrName($tlaHome, $tlaAway, $name_home, $away_name,$competitionId)
    {
        $query = $this->model->newQuery();
        $query->where('competition_id', $competitionId)
            ->where(function ($q) use ($tlaHome, $tlaAway, $name_home, $away_name) {
                $q->whereHas('homeTeam', function ($query) use ($tlaHome, $name_home) {
                    $query->where('tla', $tlaHome, $name_home)
                        ->orWhere('name', 'like', '%' . $tlaHome . '%')
                        ->orWhere('name', 'like', '%' . $name_home . '%')
                        ->orWhere('short_name', 'like', '%' . $name_home . '%');
                });
                $q->WhereHas('awayTeam', function ($query) use ($tlaAway, $away_name) {
                        $query->where('tla', $tlaAway, $away_name)
                            ->orWhere('name', 'like', '%' . $tlaAway . '%')
                            ->orWhere('name', 'like', '%' . $away_name . '%')
                            ->orWhere('short_name', 'like', '%' . $away_name . '%');
                        $query->where('tla', $tlaAway);

                    });
            });
            $query->where('status', 'FINISHED')
            ->where('utc_date', '<=', now());

        return $query->orderBy('utc_date', 'desc')
            // ->with(['homeTeam', 'awayTeam'])
            ->first();
    }
}
