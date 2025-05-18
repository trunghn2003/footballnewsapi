<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use ApiResponseTrait;

    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function getCommentsByNews(Request $request, $newsId)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $comments = $this->commentService->getCommentsByNews($newsId, $perPage);
            return $this->successResponse($comments);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function createComment(Request $request)
    {
        try {
            $data = $request->validate([
                'content' => 'required|string',
                'news_id' => 'required|exists:news,id',
                'parent_id' => 'nullable'
            ]);

            $data['user_id'] = auth()->id();
            $comment = $this->commentService->createComment($data);
            return $this->successResponse($comment);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateComment(Request $request, $commentId)
    {
        try {
            $data = $request->validate([
                'content' => 'required|string'
            ]);

            $comment = $this->commentService->updateComment($commentId, $data);
            return $this->successResponse($comment);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function deleteComment($commentId)
    {
        try {
            $this->commentService->deleteComment($commentId);
            return $this->successResponse(null, 'Comment deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getCommentById($commentId)
    {
        try {
            $comment = $this->commentService->getCommentById($commentId);
            return $this->successResponse($comment);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
