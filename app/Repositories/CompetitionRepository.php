<?php

namespace App\Repositories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use mysql_xdevapi\Collection;

class CompetitionRepository
{
    /**
     * Create or update a Competition.
     *
     * @param array $data
     * @return Competition
     */
    private $model;

    public function __construct(Competition $model)
    {
        $this->model = $model;
    }
    public function createOrUpdate(array $data): Competition
    {
        return Competition::updateOrCreate(
            ['id' => $data['id']],
            [
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'type' => $data['type'],
                'emblem' => $data['emblem'] ?? null,
                'area_id' => $data['area_id'],
                'last_updated' => $data['lastUpdated'] ?? now(),
            ]
        );
    }

    /**
     * Find a Competition by  ID.
     *
     * @param int $Id
     * @return Competition|null
     */
    public function findById(int $Id): ?Competition
    {
        return Competition::where('id', $Id)->first();
    }
    public function getByIds(array $ids)
    {
        // dd($ids);
        return Competition::whereIn('id', $ids)->get();
    }

    public function findByName(string $name)
    {
        return Competition::where('name', $name)->orWhere('code', $name)
            ->with(['area', 'currentSeason'])
        ->first();
    }



    public function getAll($filters, $perPage, $page)
    {
        try {
            $query = $this->model->query();

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            if (isset($filters['code'])) {
                $query->where('code', 'like', '%' . $filters['code'] . '%');
            }
            if (isset($filters['type'])) {
                $query->where('type', 'like', '%' . $filters['type'] . '%');
            }
            if (isset($filters['area_id'])) {
                $query->where('area_id', '=', $filters['area_id']);
            }
            $query->orderBy('is_featured', 'desc');

            $query = $query->paginate($perPage, ['*'], 'page', $page);
            return $query;
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    public function getById(int $Id)
    {
        try {
            $query = $this->model->findOrFail($Id);
            return $query;
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
    }

    /**
     * Get featured competitions
     */
    public function getFeatured()
    {
        return $this->model->where('is_featured', true)
            ->with(['area', 'currentSeason'])
            ->get();
    }
}
