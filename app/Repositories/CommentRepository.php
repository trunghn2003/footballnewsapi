<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentRepository
{
    protected $model;

    public function __construct(Comment $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $comment = new Comment();
            $comment->parent_id = $data['parent_id'] ?? null;
            $comment->content = $data['content'];
            $comment->user_id = $data['user_id'];
            $comment->news_id = $data['news_id'];
            $comment->save();

            DB::commit();
            return $comment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating comment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCommentsByNews($newsId, $perPage = 10)
    {
        return $this->model
            ->where('news_id', $newsId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function update($commentId, array $data)
    {
        DB::beginTransaction();
        try {
            $comment = $this->model->findOrFail($commentId);
            $comment->content = $data['content'];
            $comment->save();

            DB::commit();
            return $comment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating comment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete($commentId)
    {
        DB::beginTransaction();
        try {
            $comment = $this->model->findOrFail($commentId);
            $comment->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting comment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCommentById($commentId)
    {
        return $this->model->with('user')->findOrFail($commentId);
    }
}
