<?php

namespace App\Services;

use App\Repositories\CommentRepository;
use Illuminate\Support\Facades\Log;

class CommentService
{
    protected $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function createComment(array $data)
    {
        try {
            return $this->commentRepository->create($data);
        } catch (\Exception $e) {
            Log::error('Error in CommentService createComment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCommentsByNews($newsId, $perPage = 10)
    {
        try {
            $result = $this->commentRepository->getCommentsByNews($newsId, $perPage);
            return [
                'comments' => $result->items(),
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'per_page'     => $result->perPage(),
                    'total'        => $result->total()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error in CommentService getCommentsByNews: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateComment($commentId, array $data)
    {
        try {
            return $this->commentRepository->update($commentId, $data);
        } catch (\Exception $e) {
            Log::error('Error in CommentService updateComment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteComment($commentId)
    {
        try {
            return $this->commentRepository->delete($commentId);
        } catch (\Exception $e) {
            Log::error('Error in CommentService deleteComment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCommentById($commentId)
    {
        try {
            return $this->commentRepository->getCommentById($commentId);
        } catch (\Exception $e) {
            Log::error('Error in CommentService getCommentById: ' . $e->getMessage());
            throw $e;
        }
    }
} 