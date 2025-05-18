<?php

namespace App\Repositories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationRepository
{
    protected $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    public function getNotificationsByUser($limit = 10)
    {
        return $this->model->where('user_id', auth()->user()->id)
            // ->where('notifiable_type', 'App\Models\User')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    public function markAsRead($notificationId)
    {
        try {
            $notification = $this->model->findOrFail($notificationId);
            $notification->is_read = 1;
            // dd($notification);
            // $notification->read_at = now();
            $notification->save();
            return true;
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
            return false;
        }
    }
    public function deleteNotification($notificationId)
    {
        try {
            $notification = $this->model->findOrFail($notificationId);
            return $notification->delete();
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
            return false;
        }
    }
    public function getUnreadCount($userId)
    {
        return $this->model->where('id', $userId)
            ->where('notifiable_type', 'App\Models\User')
            ->whereNull('read_at')
            ->count();
    }
    public function getNotificationById($notificationId)
    {
        try {
            return $this->model->findOrFail($notificationId);
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
            return false;
        }
    }
    public function createNotification(array $data)
    {
        return $this->model->create($data);
    }
    public function updateNotification($notificationId, array $data)
    {
        try {
            $notification = $this->model->findOrFail($notificationId);
            $notification->update($data);
            return $notification;
        } catch (\Exception $e) {
            throw new ModelNotFoundException($e->getMessage());
            return false;
        }
    }
}
