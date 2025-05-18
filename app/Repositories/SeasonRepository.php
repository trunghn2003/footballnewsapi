<?php

namespace App\Repositories;

use App\Models\Season;

class SeasonRepository
{
    protected $season;

    public function __construct(Season $season)
    {
        $this->season = $season;
    }

    public function syncSeason(array $data, $competitionId): Season
    {
        return $this->season->updateOrCreate(
            ['id' => $data['id']],
            [
                'competition_id' => $competitionId,
                'name' => $data['name'] ?? "{$data['startDate']} - {$data['endDate']}",
                'start_date' => $data['startDate'] ?? null,
                'end_date' => $data['endDate'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'winner_team_id' => $data['winner']['id'] ?? null,
            ]
        );
    }
    public function getByCompetitionAndYear($competitionId, $year)
    {
        return $this->season->where('competition_id', $competitionId)
            ->whereYear('start_date', '=', $year)
//            ->whereYear('end_date', '>=', $year)
            ->first();
    }
}
