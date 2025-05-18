<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SeasonService;

class SeasonController extends Controller
{
    private SeasonService $seasonService;

    public function __construct(SeasonService $seasonService)
    {
        $this->seasonService = $seasonService;
    }

    public function sync()
    {
        $this->seasonService->syncCompetitionsSeasons();
    }
}
