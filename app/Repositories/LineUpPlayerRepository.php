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
}