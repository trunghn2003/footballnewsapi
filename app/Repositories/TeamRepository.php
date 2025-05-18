<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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
                //                'short_name' => $data['shortName'],
                //                'tla' => $data['tla'],
                //                'crest' => $data['crest'],
                //                'website' => $data['website'] ?? null,
                //                'founded' => $data['founded'] ?? null,
                //                'venue' => $data['venue'] ?? null,
                'last_synced' => now(),
                'area_id' => 2267,
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

    public function getAll($filters, $perPage, $page)
    {
        try {
            $query = $this->model->query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            if (isset($filters['short_name'])) {
                $query->where('short_name', 'like', '%' . $filters['short_name'] . '%');
            }
            if (isset($filters['tla'])) {
                $query->where('tla', 'like', '%' . $filters['tla'] . '%');
            }
            if (isset($filters['area_id'])) {
                $query->where('area_id', '=', $filters['area_id']);
            }
            if (isset($filters['competition_id'])) {
                $query->whereHas('competitions', function ($q) use ($filters) {
                    $q->where('competitions.id', $filters['competition_id'])
                      // Join với bảng seasons để lấy mùa giải hiện tại
                      ->whereHas('seasons', function ($seasonQuery) {
                          $seasonQuery->whereDate('start_date', '<=', now())
                                    ->whereDate('end_date', '>=', now());
                      })
                      // Đảm bảo có record trong bảng pivot cho mùa giải hiện tại
                      ->whereHas('teams', function ($teamQuery) {
                          $teamQuery->whereHas('competitions.seasons', function ($pivotQuery) {
                              $pivotQuery->whereDate('start_date', '<=', now())
                                       ->whereDate('end_date', '>=', now());
                          });
                      });
                });
            }

            return $query->paginate($perPage, ['*'], 'page', $page);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
    public function findAll()
    {
        return $this->model->all();
    }

    public function generateNewId()
    {
        // Fetch the maximum ID and increment by 1
        $maxId = DB::table('teams')->max('id');
        return $maxId + 1;
    }

    public function findByName($name)
    {
        return $this->model->where('name', 'like', '%' . $name . '%')
                ->orWhere('short_name', 'like', '%' . $name . '%')
                ->orWhere('tla', 'like', '%' . $name . '%')
                ->first();
    }
    public function getFavoriteTeams()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        $favoriteTeams = $user->favourite_teams;
        if (!is_array($favoriteTeams)) {
            $favoriteTeams = json_decode($favoriteTeams, true) ?? [];
        }
        return $this->model->whereIn('id', $favoriteTeams)->get();
    }
}
