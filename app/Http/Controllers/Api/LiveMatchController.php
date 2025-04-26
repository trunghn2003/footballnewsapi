<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveMatchService;
use Illuminate\Http\Request;

class LiveMatchController extends Controller
{
    protected $liveMatchService;

    public function __construct(LiveMatchService $liveMatchService)
    {
        $this->liveMatchService = $liveMatchService;
    }

    public function getLiveMatches()
    {
        $result = $this->liveMatchService->getLiveMatches();
        return response()->json($result);
    }
}
