<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BulkIndexCompetitionsJob;
use App\Jobs\BulkIndexFixturesJob;
use App\Jobs\BulkIndexNewsJob;
use App\Jobs\BulkIndexTeamsJob;
use Illuminate\Http\Request;
use App\Services\RAGService;
use Illuminate\Support\Facades\Validator;

class RAGController extends Controller
{
    protected $ragService;

    public function __construct(RAGService $ragService)
    {
        $this->ragService = $ragService;
    }

    public function ask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|min:3|max:1000',
            'type' => 'nullable|string|in:news,team,competition'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        $result = $this->ragService->searchAndGenerateResponse(
            $request->input('question'),
            $request->input('type')
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    public function bulkIndexNews()
    {
        BulkIndexNewsJob::dispatch();
        return response()->json([
            'success' => true,
            'message' => 'Bulk index news job has been queued'
        ]);
    }

    public function bulkIndexTeams()
    {
        BulkIndexTeamsJob::dispatch();
        return response()->json([
            'success' => true,
            'message' => 'Bulk index teams job has been queued'
        ]);
    }

    public function bulkIndexCompetitions()
    {
        BulkIndexCompetitionsJob::dispatch();
        return response()->json([
            'success' => true,
            'message' => 'Bulk index competitions job has been queued'
        ]);
    }

    public function bulkIndexFixtures()
    {
        BulkIndexFixturesJob::dispatch();
        return response()->json([
            'success' => true,
            'message' => 'Bulk index fixtures job has been queued'
        ]);
    }

    public function bulkIndexSeasons()
    {
        // BulkIndexSeasonsJob::dispatch();
        return response()->json([
            'success' => true,
            'message' => 'Bulk index seasons job has been queued'
        ]);
    }
}
