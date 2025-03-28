<?php

namespace App\Repositories;

use App\Models\Player;

class PlayerRepository
{
    /**
     * Update or create a player.
     *
     * @param array $data
     * @param int $teamId
     * @return Player
     */
    public function updateOrCreatePlayer(array $data, int $teamId): Player
    {
        return Player::updateOrCreate(
            ['id' => $data['id']],
            [
                'team_id' => $teamId,
                'name' => $data['name'],
                'position' => $data['position'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'date_of_birth' => $data['dateOfBirth'] ?? null,
                'last_synced' => now(),
            ]
        );
    }
    
}
