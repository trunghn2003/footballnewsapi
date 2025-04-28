<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RAGService;
use Illuminate\Http\Request;

class RAGController extends Controller
{
    protected $ragService;

    public function __construct(RAGService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Trả lời câu hỏi của người dùng
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'type' => 'nullable|string|in:news,team,competition'
        ]);

        $answer = $this->ragService->searchAndGenerateResponse(
            $request->question,
            $request->type
        );

        return response()->json([
            'status' => true,
            'data' => [
                'question' => $request->question,
                'answer' => $answer
            ]
        ]);
    }

    /**
     * Index tất cả dữ liệu vào RAG system
     */
    public function indexAll()
    {
        $this->ragService->bulkIndexNews();
        $this->ragService->bulkIndexTeams();

        return response()->json([
            'status' => true,
            'message' => 'Đã index thành công tất cả dữ liệu'
        ]);
    }
}
