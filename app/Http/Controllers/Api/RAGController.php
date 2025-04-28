<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $result = $this->ragService->bulkIndexNews();
        return response()->json($result);
    }

    public function bulkIndexTeams()
    {
        $result = $this->ragService->bulkIndexTeams();
        return response()->json($result);
    }

    public function bulkIndexCompetitions()
    {
        $result = $this->ragService->bulkIndexCompetitions();
        return response()->json($result);
    }

    public function bulkIndexFixtures()
    {
        $result = $this->ragService->bulkIndexFixtures();
        return response()->json($result);
    }

    public function bulkIndexSeasons()
    {
        $result = $this->ragService->bulkIndexSeasons();
        return response()->json($result);
    }
}
