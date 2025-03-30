<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentRepository
{
    protected $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function getCommentsByNews($newsId)
    {
        return $this->comment->with(['user', 'replies'])
            ->where('news_id', $newsId)
            ->whereNull('parent_id')
            ->get();
    }

    public function getRepliesByComment($commentId)
    {
        return $this->comment->with('user')
            ->where('parent_id', $commentId)
            ->get();
    }

    public function createComment(array $data)
    {
        return $this->comment->create($data);
    }

    public function deleteComment($commentId)
    {
        try{
            $comment = $this->comment->findOrFail($commentId);
            return $comment->delete();

        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
            return false;
        }
    }
}
