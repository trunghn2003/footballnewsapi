<?php
namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationService
{

    private $notificationRepository;
    use PushNotification;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }
    /**
     *send notification to user.
     *
     * @param User $user
     * @param string $type
     * @param array $data o.
     * @param array $channels
     * @return bool
     */
    public function send(User $user, string $type, array $data, array $channels = ['push']): bool
    {
        // dd($user, $data);
        $notification = $this->createNotification($user, $type, $data);
        // dd($user);
                    if($user->fcm_token) {
                    // dd($data);
                    $this->sendNotification($user->fcm_token,$type,$data ,$data);

                    }
        return true;
    }

    /**
     * create a new notification record.
     */
    protected function createNotification(User $user, string $type, array $data): Notification
    {
        return $this->notificationRepository->createNotification([
            'user_id' => $user->id,
            'title' => $data['title'] ?? null,
            'type' => $type,
            'data' => $data,
            'status' => 'pending',
            'message' => $data['message'] ?? null,
        ]);
    }






}
