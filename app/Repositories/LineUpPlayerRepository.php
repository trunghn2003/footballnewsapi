<?php

namespace App\Repositories;

use App\Models\LineupPlayer;
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
}
