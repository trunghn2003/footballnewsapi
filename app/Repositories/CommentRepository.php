<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentRepository
{
    protected $model;
    use PushNotification;

    public function __construct(Comment $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $comment = new Comment();
            // dd($data);
            $comment->parent_id = $data['parent_id'] ?? null;
            $comment->content = $data['content'];
            $comment->user_id = $data['user_id'];
            $comment->news_id = $data['news_id'];
            $comment->save();            // Send push notification if this is a reply
            if ($comment->parent_id) {
                $parentComment = $this->model->with('user')->find($comment->parent_id);
                if ($parentComment && $parentComment->user_id !== $data['user_id'] && $parentComment->user->fcm_token) {
                    $title =  auth()->user()->name .  " đã trả lời bình luận của bạn";
                    $message = $comment->content;

                    $this->sendNotification(
                        $parentComment->user->fcm_token,
                        $title,
                        $message,
                        [
                            'type' => 'comment_reply',
                            'comment_id' => $comment->id,
                            'news_id' => $comment->news_id,
                            'replier_name' => auth()->user()->name,
                            'content' => $comment->content,
                            'screen' => "NewsView/?id=" . $comment->news_id,
                            'user_id' => $parentComment->user_id,
                        ]
                    );
                }
            }

            DB::commit();
            return $comment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating comment: ' . $e->getMessage());
            throw $e;
        }
    }    public function getCommentsByNews($newsId)
    {
        return $this->model
            ->where('news_id', $newsId)
            ->where('parent_id', null) // Get only parent comments
            ->with(['user', 'replies.user']) // Load replies and their users
            ->orderBy('created_at', 'desc');
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
