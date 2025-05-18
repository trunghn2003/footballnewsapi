<?php

namespace App\Services;

use App\Mapper\CompetitionMapper;
use App\Models\Competition;
use App\Models\User;
use App\Repositories\CompetitionRepository;
use App\Repositories\PersonRepository;
use App\Repositories\TeamRepository;
use App\Repositories\LineUpPlayerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamService
{
    private string $apiUrl;
    private string $apiToken;


    public function __construct(
        private TeamRepository $teamRepository,
        private CompetitionRepository $competitionRepository,
        private PersonRepository $personRepository,
        private LineUpPlayerRepository $lineUpPlayerRepository,
        private CompetitionMapper $competitionMapper
    ) {
        $this->apiUrl = config('services.football_api.url');
        $this->apiToken = config('services.football_api.token');
    }

     /**
     * Sync Premier League teams and players.
     *
     * @return bool
     */
    public function syncTeamsAndPlayers(): bool
    {
        set_time_limit(30000000);
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
            foreach ($names as $name => $id1) {
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
                        // DB::table('team_competition_season')->updateOrInsert(
                        //     [
                        //         'team_id' => $team->id,
                        //         'competition_id' => $competition->id,
                        //         'season_id' => $currentSeason->id
                        //     ],
                        //     ['created_at' => now(), 'updated_at' => now()]
                        // );
                        Log::info('Team: ' . $team->name . ' ' . $competition->id . ' ' . $currentSeason->id . ' synced successfully.');
                        foreach ($teamData['squad'] as $playerData) {
                            $this->personRepository->syncPerson($playerData, $team->id);
                        }
                    }
                });
                DB::commit();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('League sync failed: ' . $e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    public function getTeamById(int $id)
    {
        $result = $this->teamRepository->findById($id);
        $competition = $result->competitions()->get();
        $competionDtos = [];
        foreach ($competition as $item) {
                    $competionDtos[] = $this->competitionMapper->toDTO($item);
        }
        $players = $result->players()->get();

        // Lấy ID của tất cả cầu thủ
        $playerIds = $players->pluck('id')->toArray();

        // Sử dụng LineUpPlayerRepository để lấy thông tin số áo
        $lineupInfo = $this->lineUpPlayerRepository->getLatestPlayersInfo($playerIds);
        $lineupInfoMap = $lineupInfo->keyBy('player_id');

        // Thêm thông tin số áo và vị trí vào đối tượng cầu thủ
        $playersWithShirtNumber = $players->map(function ($player) use ($lineupInfoMap) {
            $playerData = $player->toArray();

            if ($lineupInfoMap->has($player->id)) {
                $info = $lineupInfoMap->get($player->id);
                $playerData['shirt_number'] = $info->shirt_number;
                $playerData['position'] = $info->position;
            } else {
                $playerData['shirt_number'] = null;
                $playerData['position'] = null;
            }

            return $playerData;
        });

        // Sắp xếp cầu thủ theo số áo
        $sortedPlayers = $playersWithShirtNumber->sort(function ($a, $b) {
            if ($a['shirt_number'] === null && $b['shirt_number'] === null) {
                return 0;
            }
            if ($a['shirt_number'] === null) {
                return 1;  // Đưa cầu thủ không có số áo xuống cuối
            }
            if ($b['shirt_number'] === null) {
                return -1; // Đưa cầu thủ không có số áo xuống cuối
            }

            // So sánh số áo
            return $a['shirt_number'] <=> $b['shirt_number'];
        })->values();  // values() để reset lại các key sau khi sắp xếp

        return [
            'team' => $result,
            'players' => $sortedPlayers,
            'competitions' => $competionDtos,
        ];
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

    public function getFavoriteTeams(): array
    {
        $user = User::where('id', auth()->user()->id)->first();
        if (!$user) {
            return [];
        }
        $favoriteTeams = $user->favourite_teams;
        if (!is_array($favoriteTeams)) {
            $favoriteTeams = json_decode($favoriteTeams, true) ?? [];
        }
        $result =  $this->teamRepository->getFavoriteTeams($favoriteTeams);
        if (!isset($result) || $result->isEmpty()) {
            return [
                'teams' => [],

            ];
        }
        return [
            'teams' => $result ,
        ];
    }

    /**
     * Get team statistics for a specific competition
     *
     * @param int $teamId ID của đội bóng
     * @param int $competitionId ID của giải đấu
     * @param int|null $seasonId ID của mùa giải (nếu không cung cấp, sẽ sử dụng mùa giải hiện tại)
     * @return array
     */
    public function getTeamStatsByCompetition(int $teamId, int $competitionId, ?int $seasonId = null): array
    {
        try {
            // Lấy thông tin đội bóng
            $team = $this->teamRepository->findById($teamId);
            if (!$team) {
                throw new \Exception('Team not found');
            }

            // Lấy thông tin giải đấu
            $competition = $this->competitionRepository->findById($competitionId);
            if (!$competition) {
                throw new \Exception('Competition not found');
            }

            // Nếu không cung cấp seasonId, lấy mùa giải hiện tại
            if (!$seasonId && $competition->currentSeason) {
                $seasonId = $competition->currentSeason->id;
            }

            if (!$seasonId) {
                throw new \Exception('Season not found');
            }

            // Lấy thống kê từ bảng standings
            $standings = app()->make(\App\Repositories\StandingRepository::class)->getStandingsByCompetitionAndSeason(
                $competitionId,
                $seasonId,
                $competition->currentSeason->current_matchday ?? 1,
                'TOTAL',
                $teamId
            );

            // Lấy lịch sử trận đấu của team trong giải đấu
            $fixtureRepo = app()->make(\App\Repositories\FixtureRepository::class);
            $seasonId = $competition->currentSeason ? $competition->currentSeason->id : null;
            $fixtures = $fixtureRepo->getFixtures([
                'teamId' => $teamId,
                'competition_id' => $competitionId,
                'status' => 'FINISHED',
                'season_id' => $seasonId,
                'recently' => 1
            ], 100, 1);

            // Tính toán số liệu thống kê dựa trên fixtures
            $fixtureStats = [
                'total_matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'clean_sheets' => 0,
                'failed_to_score' => 0,
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
                'biggest_win' => null,
                'biggest_loss' => null,
                'form' => '',
                'recent_fixtures' => []
            ];

            // Xử lý dữ liệu trận đấu
            foreach ($fixtures->items() as $fixture) {
                $homeScore = $fixture->full_time_home_score ?? 0;
                $awayScore = $fixture->full_time_away_score ?? 0;
                $isHomeTeam = $fixture->home_team_id == $teamId;
                $goalDiff = $isHomeTeam ? ($homeScore - $awayScore) : ($awayScore - $homeScore);

                // Cập nhật số liệu tổng quát
                $fixtureStats['total_matches']++;

                // Thêm vào recent fixtures (giới hạn 5)
                if (count($fixtureStats['recent_fixtures']) < 5) {
                    $fixtureStats['recent_fixtures'][] = [
                        'id' => $fixture->id,
                        'date' => $fixture->utc_date,
                        'home_team' => [
                            'id' => $fixture->home_team_id,
                            'name' => $fixture->homeTeam ? $fixture->homeTeam->name : 'Unknown',
                            'score' => $homeScore
                        ],
                        'away_team' => [
                            'id' => $fixture->away_team_id,
                            'name' => $fixture->awayTeam ? $fixture->awayTeam->name : 'Unknown',
                            'score' => $awayScore
                        ],
                        'status' => $fixture->status
                    ];
                }

                // Tính thống kê dựa trên việc là đội nhà hay đội khách
                if ($isHomeTeam) {
                    // Đội chủ nhà
                    $fixtureStats['home_goals_for'] += $homeScore;
                    $fixtureStats['home_goals_against'] += $awayScore;
                    $fixtureStats['goals_for'] += $homeScore;
                    $fixtureStats['goals_against'] += $awayScore;

                    if ($homeScore > $awayScore) {
                        $fixtureStats['wins']++;
                        $fixtureStats['home_wins']++;
                        $fixtureStats['form'] .= 'W';
                    } elseif ($homeScore < $awayScore) {
                        $fixtureStats['losses']++;
                        $fixtureStats['home_losses']++;
                        $fixtureStats['form'] .= 'L';
                    } else {
                        $fixtureStats['draws']++;
                        $fixtureStats['home_draws']++;
                        $fixtureStats['form'] .= 'D';
                    }

                    if ($awayScore == 0) {
                        $fixtureStats['clean_sheets']++;
                    }

                    if ($homeScore == 0) {
                        $fixtureStats['failed_to_score']++;
                    }
                } else {
                    // Đội khách
                    $fixtureStats['away_goals_for'] += $awayScore;
                    $fixtureStats['away_goals_against'] += $homeScore;
                    $fixtureStats['goals_for'] += $awayScore;
                    $fixtureStats['goals_against'] += $homeScore;

                    if ($awayScore > $homeScore) {
                        $fixtureStats['wins']++;
                        $fixtureStats['away_wins']++;
                        $fixtureStats['form'] .= 'W';
                    } elseif ($awayScore < $homeScore) {
                        $fixtureStats['losses']++;
                        $fixtureStats['away_losses']++;
                        $fixtureStats['form'] .= 'L';
                    } else {
                        $fixtureStats['draws']++;
                        $fixtureStats['away_draws']++;
                        $fixtureStats['form'] .= 'D';
                    }

                    if ($homeScore == 0) {
                        $fixtureStats['clean_sheets']++;
                    }

                    if ($awayScore == 0) {
                        $fixtureStats['failed_to_score']++;
                    }
                }

                // Theo dõi kết quả lớn nhất
                if (
                    $goalDiff > 0 &&
                    (!$fixtureStats['biggest_win'] || $goalDiff > $fixtureStats['biggest_win']['goal_difference'])
                ) {
                    $fixtureStats['biggest_win'] = [
                        'id' => $fixture->id,
                        'date' => $fixture->utc_date,
                        'home_team' => [
                            'id' => $fixture->home_team_id,
                            'name' => $fixture->homeTeam ? $fixture->homeTeam->name : 'Unknown',
                            'score' => $homeScore
                        ],
                        'away_team' => [
                            'id' => $fixture->away_team_id,
                            'name' => $fixture->awayTeam ? $fixture->awayTeam->name : 'Unknown',
                            'score' => $awayScore
                        ],
                        'goal_difference' => $goalDiff
                    ];
                }

                if (
                    $goalDiff < 0 &&
                    (!$fixtureStats['biggest_loss'] || $goalDiff < $fixtureStats['biggest_loss']['goal_difference'])
                ) {
                    $fixtureStats['biggest_loss'] = [
                        'id' => $fixture->id,
                        'date' => $fixture->utc_date,
                        'home_team' => [
                            'id' => $fixture->home_team_id,
                            'name' => $fixture->homeTeam ? $fixture->homeTeam->name : 'Unknown',
                            'score' => $homeScore
                        ],
                        'away_team' => [
                            'id' => $fixture->away_team_id,
                            'name' => $fixture->awayTeam ? $fixture->awayTeam->name : 'Unknown',
                            'score' => $awayScore
                        ],
                        'goal_difference' => $goalDiff
                    ];
                }
            }


            $fixtureStats['form'] = substr($fixtureStats['form'], -5);


            $result = [
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'shortName' => $team->short_name,
                    'tla' => $team->tla,
                    'crest' => $team->crest,
                ],
                'competition' => [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'code' => $competition->code,
                    'emblem' => $competition->emblem,
                ],
                'season' => [
                    'id' => $seasonId,
                    'startDate' => $competition->currentSeason ? $competition->currentSeason->start_date : null,
                    'endDate' => $competition->currentSeason ? $competition->currentSeason->end_date : null,
                    'currentMatchday' => $competition->currentSeason ? $competition->currentSeason->current_matchday : null,
                ],
                'standings' => !empty($standings) ? [
                    'position' => $standings[0]->position ?? null,
                    'playedGames' => $standings[0]->played_games ?? null,
                    'won' => $standings[0]->won ?? null,
                    'draw' => $standings[0]->draw ?? null,
                    'lost' => $standings[0]->lost ?? null,
                    'points' => $standings[0]->points ?? null,
                    'goalsFor' => $standings[0]->goals_for ?? null,
                    'goalsAgainst' => $standings[0]->goals_against ?? null,
                    'goalDifference' => $standings[0]->goal_difference ?? null,
                    'form' => $standings[0]->form ?? null,
                ] : null,
                'stats' => $fixtureStats,
            ];

            return $result;
        } catch (\Exception $e) {
            Log::error('Error getting team stats by competition: ' . $e->getMessage());
            throw $e;
        }
    }
}
