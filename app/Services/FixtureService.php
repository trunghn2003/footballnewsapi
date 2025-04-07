<?php

namespace App\Services;

use App\Mapper\LineupMapper;
use App\Repositories\FixtureRepository;
use App\Repositories\PersonRepository;
use App\DTO\FixtureDTO;
use App\Mapper\FixtureMapper;
use App\Mapper\TeamMapper;
use App\Models\Fixture;
use App\Models\Formation;
use App\Models\LineupPlayer;
use App\Models\User;
use App\Repositories\LineUpPlayerRepository;
use App\Repositories\LineUpRepository;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixtureService
{
    private FixtureRepository $fixtureRepository;
    private string $apiToken;
    private string $apiUrlFootball;
    private CompetitionService $competitionService;
    private TeamService $teamService;
    private LineupRepository $lineupRepository;
    private LineUpPlayerRepository $lineUpPlayerRepository;
    private PersonRepository $personRepository;
    private LineupMapper $lineupMapper;
    use PushNotification;

    public function __construct(
        FixtureRepository $fixtureRepository,
        CompetitionService $competitionService,
        TeamService $teamService,
        LineupRepository $lineupRepository,
        LineUpPlayerRepository $lineUpPlayerRepository,
        PersonRepository $personRepository,
        LineupMapper $lineupMapper,
    ) {
        $this->fixtureRepository = $fixtureRepository;
        $this->apiToken = env('API_FOOTBALL_TOKEN');
        $this->apiUrlFootball = env('API_FOOTBALL_URL');
        $this->competitionService = $competitionService;
        $this->teamService = $teamService;
        $this->lineupRepository = $lineupRepository;
        $this->lineUpPlayerRepository = $lineUpPlayerRepository;
        $this->personRepository = $personRepository;
        $this->lineupMapper = $lineupMapper;
    }

    public function syncFixtures()
    {
        try {
            $names = [
                'PL',
                'CL',
                'FL1',
                'BL1',
                'SA',
                'PD',
            ];
            foreach ($names as $name) {

                $response = Http::withHeaders([
                    'X-Auth-Token' => $this->apiToken
                ])->get("{$this->apiUrlFootball}/competitions/{$name}/matches");

                if (!$response->successful()) {
                    throw new \Exception("API request failed: {$response->status()}");
                }

                $datas = $response->json()['matches'];
                // dd($datas);

                DB::beginTransaction();

                if (isset($datas) && is_array($datas)) {
                    foreach ($datas as $data) {
                        if (isset($data['homeTeam']) && isset($data['awayTeam'])) {
                            $fixture =  $this->fixtureRepository->createOrUpdate($data);
                            // dd($fixture);
                            if ($fixture->wasRecentlyCreated) {
                            } else if ($fixture->wasChanged()) {
                                // Check if score has changed
                                if ($fixture->wasChanged(['status'])) {
                                    // dd(1);
                                    $this->sendMatchScoreNotification($fixture);
                                }
                            }
                        }
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

    private function mapPositionToGroup($position)
    {
        $map = [
            'Goalkeeper'         => 'G',
            'Left-Back'          => 'D',
            'Right-Back'         => 'D',
            'Centre-Back'        => 'D',
            'Defence'            => 'D',
            'Central Midfield'   => 'M',
            'Attacking Midfield' => 'M',
            'Defensive Midfield' => 'M',
            'Left Midfield'      => 'M',
            'Right Midfield'     => 'M',
            'Midfield'           => 'M',
            'Left Winger'        => 'F',
            'Right Winger'       => 'F',
            'Centre-Forward'     => 'F',
            'Offence'            => 'F'
        ];

        return $map[$position] ?? null;
    }

    public function createRandomLineup($fixture_id, $team_id, $players, $formation)
    {
        $formationPositions = Formation::getFormation($formation);

        $lineup = $this->lineupRepository->create([

            'fixture_id' => $fixture_id,
            'team_id'    => $team_id,
            'formation'  => $formation
        ]);

        $remainingPlayers = collect($players)->shuffle();
        $starterCount = 0;
        foreach ($formationPositions as $pos) {
            if ($starterCount >= 11) break;
            if ($remainingPlayers->isEmpty()) {
                break;
            }

            $group = $pos['group'];

            $filteredPlayers = $remainingPlayers->filter(function ($player) use ($group) {

                $playerGroup = $this->mapPositionToGroup($player->position);
                return $playerGroup === $group;
            });


            if ($filteredPlayers->isNotEmpty()) {
                $selected = $filteredPlayers->random();
            } else {
                $selected = $remainingPlayers->random();
            }

            $this->lineUpPlayerRepository->create([
                'lineup_id'    => $lineup->id,
                'player_id'    => $selected->id,
                'position'     => $pos['position'],
                'grid_position'         => $pos['grid'],
                'shirt_number' => $selected->shirt_number ?? rand(1, 99),
                'is_substitute'   => 0
            ]);
            $starterCount++;

            $remainingPlayers = $remainingPlayers->reject(function ($player) use ($selected) {
                return $player->id === $selected->id;
            });
        }
        $substituteCount = 0;
        while ($substituteCount < 7 && $remainingPlayers->isNotEmpty()) {
            $substitute = $remainingPlayers->random();


            $this->lineUpPlayerRepository->create([
                'lineup_id'     => $lineup->id,
                'player_id'     => $substitute->id,
                'position'      => null,
                'grid_position' => null,
                'shirt_number'  => $substitute->shirt_number ?? rand(1, 99),
                'is_substitute' => 1
            ]);

            $substituteCount++;


            $remainingPlayers = $remainingPlayers->reject(function ($player) use ($substitute) {
                return $player->id === $substitute->id;
            });
        }
        $totalPlayers = $starterCount + $substituteCount;
        if ($totalPlayers < 18) {
            Log::warning("Lineup {$lineup->id} created with only {$totalPlayers} players");
        } else {
            Log::info("Lineup {$lineup->id} created with {$totalPlayers} players");
        }

        return $lineup;
    }

    public function getFixtureById(int $id)
    {
        $fixture = $this->fixtureRepository->findById($id);
        $formations = ['4-4-2', '4-3-3', '3-5-2'];

        $lineups = $fixture->lineups;
        //        dd($fixture->homeLineup);
        // dump($lineups, );

        // dump(,$awayLineup);
        if (!isset($lineups) || count($lineups) == 0) {
            // dd(1);
            $this->createRandomLineup($fixture->id, $fixture->home_team_id, $fixture->homeTeam->players, $formations[rand(0, count($formations) - 1)]);
            $this->createRandomLineup($fixture->id, $fixture->away_team_id, $fixture->awayTeam->players, $formations[rand(0, count($formations) - 1)]);
        }
        if (!$fixture) {
            return null;
        }

        $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
        $fixtureDto = FixtureMapper::fromModel($fixture);
        $fixtureDto->setHomeTeam(TeamMapper::fromModel($fixture->homeTeam));
        $fixtureDto->setAwayTeam(TeamMapper::fromModel($fixture->awayTeam));
        $fixtureDto->setCompetition($competition);
        return [
            'fixture' => $fixtureDto ?? null,
            "home_lineup" => collect($this->mapLineupToArray($fixture->homeLineup)) ?? null,
            "away_lineup" => collect($this->mapLineupToArray($fixture->awayLineup)) ?? null,
        ];

        return $fixtureDto;
    }
    public function mapLineupToArray($lineup)
    {
        if (!$lineup) {
            return null;
        }
        return [

            'formation' => $lineup->formation,
            'startXI' => $lineup->lineupPlayers->filter(function ($player) {
                return $player->is_substitute == 0;
            })
                ->sortBy(function ($player) {

                    list($row, $col) = explode(':', $player->grid_position);
                    return $row * 100 + $col;
                })->map(function ($player) {
                    return [
                        'id' => $player->player_id,
                        'position' => $player->position,
                        'name' => $player->player->name,
                        'shirt_number' => $player->shirt_number,
                        'is_substitute' => $player->is_substitute,
                        'grid' => $player->grid_position,
                    ];
                }),
            'sub' => $lineup->lineupPlayers->filter(function ($player) {
                return $player->is_substitute == 1;
            })->map(function ($player) {
                return [
                    'id' => $player->player_id,
                    'position' => $player->position,
                    'name' => $player->player->name,
                    'shirt_number' => $player->shirt_number,
                    'is_substitute' => $player->is_substitute,
                ];
            }),
        ];
    }

    public function getFixtures(array $filters = [], int $perPage = 10, int $page = 1): array
    {
        $fixtures = $this->fixtureRepository->getFixtures($filters, $perPage, $page);
        if (isset($fixtures) && count($fixtures) > 0)
            // dd($fixtures->items());
            return [
                'fixtures'  => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto  = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page'     => $fixtures->perPage(),
                    'total'        => $fixtures->total()
                ]
            ];
        else return [
            'fixtures'  => [],
            'pagination' => [
                'current_page' => 0,
                'per_page'     => 0,
                'total'        => 0
            ]
        ];
    }

    public function getFixtureByCompetition($filters)
    {
        $fixtures = $this->fixtureRepository->getFixtures($filters, 50, 1, $flag = true);
        if (isset($fixtures) && count($fixtures) > 0)

            return [
                'fixtures'  => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto  = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page'     => $fixtures->perPage(),
                    'total'        => $fixtures->total()
                ]
            ];
        return [
            'fixtures'  => [],
            'pagination' => [
                'current_page' => 0,
                'per_page'     => 0,
                'total'        => 0
            ]
        ];
    }

    public function getRecentFixturesByTeam(int $teamId, int $limit = 5): array
    {
        // dd(1);
        $fixtures = $this->fixtureRepository->getFixturesRecent([
            'teamId' => $teamId,
            'status' => 'FINISHED'
        ], $limit, 1);

        if (!empty($fixtures->items())) {
            return [
                'fixtures' => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        }

        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    public function getUpcomingFixturesByTeam(int $teamId, int $limit = 5): array
    {
        $fixtures = $this->fixtureRepository->getFixtures([
            'teamId' => $teamId,
            'status' => 'TIMED'
        ], $limit, 1);

        if (isset($fixtures) && count($fixtures) > 0) {
            return [
                'fixtures' => array_map(function ($fixture) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $fixtureDto->setHomeTeam((TeamMapper::fromModel($homeTeam)));
                    }
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $fixtureDto->setAwayTeam((TeamMapper::fromModel($awayTeam)));
                    }
                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        }

        return [
            'fixtures' => [],
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }

    protected function getUsersToNotify(Fixture $match)
    {
        return User::whereJsonContains('favourite_teams', $match->homeTeam->id)
            ->orWhereJsonContains('favourite_teams', $match->awayTeam->id)
            ->get();
    }

    /**
     * Send match score notification to users
     *
     * @param Fixture $fixture
     * @return void
     */
    protected function sendMatchScoreNotification(Fixture $fixture)
    {
        // Get users who have this match's teams in their favorites
        $users = $this->getUsersToNotify($fixture);

        if ($users->isEmpty()) {
            return;
        }

        $homeTeam = $fixture->homeTeam;
        $awayTeam = $fixture->awayTeam;

        if (!$homeTeam || !$awayTeam) {
            return;
        }

        $homeScore = $fixture->full_time_home_score ?? 0;
        $awayScore = $fixture->full_time_away_score ?? 0;

        $title = "Kết quả trận đấu của " . $homeTeam->name . " và " . $awayTeam->name;
        $body = "{$homeTeam->name} {$homeScore} - {$awayScore} {$awayTeam->name}";

        foreach ($users as $user) {
            if (empty($user->fcm_token)) {
                continue;
            }

            $this->sendNotification(
                $user->fcm_token,
                $title,
                $body,
                [
                    'user_id' => $user->id,
                    'type' => 'match_score',
                    'fixture_id' => $fixture->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'home_team_name' => $homeTeam->name,
                    'away_team_name' => $awayTeam->name,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'competition_id' => $fixture->competition_id,
                    'competition_name' => $fixture->competition->name ?? 'Unknown Competition'
                ]
            );
        }

        \Log::info("Match score notification sent for fixture ID: {$fixture->id}");
    }

    /**
     * Lấy lịch sử đối đầu giữa hai đội bóng dựa trên ID trận đấu
     *
     * @param int $fixtureId ID của trận đấu
     * @param int $limit Số lượng trận đấu muốn lấy
     * @return array
     */
    public function getHeadToHeadFixturesByFixtureId(int $fixtureId, int $limit = 10): array
    {
        $result = $this->fixtureRepository->getHeadToHeadFixturesByFixtureId($fixtureId, $limit);
        $fixtures = $result['fixtures'];
        $stats = $result['stats'];

        if ($fixtures->count() > 0) {
            return [
                'fixtures' => array_map(function ($fixture) use ($stats) {
                    $competition = $this->competitionService->getCompetitionById($fixture->competition_id);
                    $fixtureDto = FixtureMapper::fromModel($fixture);

                    // Lấy thông tin đội chủ nhà
                    $homeTeam = $fixture->homeTeam;
                    if (isset($homeTeam)) {
                        $homeTeamDto = TeamMapper::fromModel($homeTeam);

                        // Thêm thống kê đối đầu cho đội chủ nhà
                        $homeTeamId = $homeTeam->id;
                        $homeTeamStats = $homeTeamId == $stats['team1']['id'] ? $stats['team1'] : $stats['team2'];
                        $homeTeamDto->setHeadToHeadStats($homeTeamStats);

                        $fixtureDto->setHomeTeam($homeTeamDto);
                    }

                    // Lấy thông tin đội khách
                    $awayTeam = $fixture->awayTeam;
                    if (isset($awayTeam)) {
                        $awayTeamDto = TeamMapper::fromModel($awayTeam);

                        // Thêm thống kê đối đầu cho đội khách
                        $awayTeamId = $awayTeam->id;
                        $awayTeamStats = $awayTeamId == $stats['team1']['id'] ? $stats['team1'] : $stats['team2'];
                        $awayTeamDto->setHeadToHeadStats($awayTeamStats);

                        $fixtureDto->setAwayTeam($awayTeamDto);
                    }

                    $fixtureDto->setCompetition($competition);
                    return $fixtureDto;
                }, $fixtures->items()),
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $fixtures->currentPage(),
                    'per_page' => $fixtures->perPage(),
                    'total' => $fixtures->total()
                ]
            ];
        }

        return [
            'fixtures' => [],
            'stats' => $stats,
            'pagination' => [
                'current_page' => 0,
                'per_page' => 0,
                'total' => 0
            ]
        ];
    }
}
