<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TeamRepository
{
    /**
     * Update or create a team.
     *
     * @param array $data
     * @return Team
     */

     private $model;

     public function __construct(Team $team)
     {
        $this->model = $team;
     }
    public function updateOrCreateTeam(array $data): Team
    {
        try {
            $team =  Team::updateOrCreate(
                ['id' => $data['id']],
                [
                'name' => $data['name'],
                'short_name' => $data['shortName'],
                'tla' => $data['tla'],
                'crest' => $data['crest'],
                'website' => $data['website'] ?? null,
                'founded' => $data['founded'] ?? null,
                'venue' => $data['venue'] ?? null,
                'last_synced' => now(),
                'area_id' => $data['area']['id'],
                'last_updated' => now()
            ]
        );
        return $team;
        } catch (\Exception $e) {
            \Log::error('Error updating or creating team: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Link a team to a competition (many-to-many relationship).
     *
     * @param Team $team
     * @param int $competitionId
     * @return void
     */
    public function linkTeamToCompetition(Team $team, int $competitionId): void
    {
        $team->competitions()->syncWithoutDetaching([$competitionId]);
    }

    public function findById(int $id): ?Team
    {
        try {
            return $this->model->find($id);
        } catch (\Exception $e) {
            \Log::error('Error finding team by id: ' . $e->getMessage());
            throw new ModelNotFoundException($e->getMessage());
        }
    }
}