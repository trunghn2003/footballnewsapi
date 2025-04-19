<?php

namespace App\Repositories;

use App\Models\LineupPlayer;
use Illuminate\Support\Facades\DB;

class LineUpPlayerRepository
{
    private $model;
    public function __construct(LineupPlayer $model)
    {
        $this->model = $model;
    }
    public function create(array $data)
    {
        return $this->model->create($data);
    }
    public function updateOrCreate(array $attributes, array $values)
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Lấy thông tin số áo và vị trí mới nhất của cầu thủ
     *
     * @param int $playerId ID của cầu thủ
     * @return object|null Thông tin số áo và vị trí hoặc null nếu không tìm thấy
     */
    public function getLatestPlayerInfo(int $playerId)
    {
        return DB::table('lineup_players')
            ->join('lineups', 'lineups.id', '=', 'lineup_players.lineup_id')
            ->join('fixtures', 'fixtures.id', '=', 'lineups.fixture_id')
            ->where('lineup_players.player_id', $playerId)
            ->orderBy('fixtures.utc_date', 'desc')
            ->select('lineup_players.shirt_number', 'lineup_players.position')
            ->first();
    }

    /**
     * Lấy thông tin số áo và vị trí mới nhất cho nhiều cầu thủ
     *
     * @param array $playerIds Mảng chứa ID của các cầu thủ
     * @return \Illuminate\Support\Collection Collection chứa thông tin số áo và vị trí của các cầu thủ
     */
    public function getLatestPlayersInfo(array $playerIds)
    {
        return DB::table('lineup_players')
            ->join('lineups', 'lineups.id', '=', 'lineup_players.lineup_id')
            ->join('fixtures', 'fixtures.id', '=', 'lineups.fixture_id')
            ->whereIn('lineup_players.player_id', $playerIds)
            ->orderBy('fixtures.utc_date', 'desc')
            ->select('lineup_players.player_id', 'lineup_players.shirt_number', 'lineup_players.position')
            ->get()
            ->unique('player_id');
    }
}
