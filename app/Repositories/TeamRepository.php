<?php

namespace App\Repositories;

use App\Models\Team;

class TeamRepository
{
    /**
     * Update or create a team.
     *
     * @param array $data
     * @return Team
     */
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
}