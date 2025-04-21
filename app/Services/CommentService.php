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
            $result = $this->commentRepository->getCommentsByNews($newsId);
            $comments = $result->get()->map(function ($comment) {
                $data = [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->avatar
                    ],
                    'replies' => []
                ];

                if ($comment->replies) {
                    $data['replies'] = $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'created_at' => $reply->created_at,
                            'user' => [
                                'id' => $reply->user->id,
                                'name' => $reply->user->name,
                                'avatar' => $reply->user->avatar
                            ]
                        ];
                    });
                }

                return $data;
            });

            return [
                'comments' => $comments
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
