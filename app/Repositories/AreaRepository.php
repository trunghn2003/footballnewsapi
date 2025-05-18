<?php

namespace App\Repositories;

use App\Models\Area;

class AreaRepository
{
    protected $model;

    public function __construct(Area $area)
    {
        $this->model = $area;
    }

    /**
     * Create or update an Area.
     *
     * @param array $data
     * @return Area
     */
    public function createOrUpdate(array $data): Area
    {

        return $this->model::updateOrCreate(
            ['id' => $data['id']],
            [
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'flag' => $data['flag'] ?? null,
            ]
        );
    }

    /**
     * Find an Area by  ID.
     *
     * @param int $id
     * @return Area|null
     */
    public function findById(int $id): ?Area
    {
        return $this->model::where('id', $id)->first();
    }

    public function getPaginatedAreas($filters, $perPage, $page)
    {
        $query = $this->model->query();

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        $areas = $query->paginate($perPage, ['*'], 'page', $page);

        return $areas;
    }
}
