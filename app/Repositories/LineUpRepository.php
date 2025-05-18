<?php

namespace App\Repositories;

use App\Models\Lineup;

class LineupRepository
{
    private $model;
    public function __construct(Lineup $model)
    {
        $this->model = $model;
    }
    public function create(array $data)
    {
        return $this->model->create($data);
    }
}
