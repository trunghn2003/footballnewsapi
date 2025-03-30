<?php
namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationService
{

    private $notificationRepository;


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

        $notification = $this->createNotification($user, $type, $data);


        foreach ($channels as $channel) {
            switch ($channel) {
                case 'push':
                    $this->sendPushNotification($user, $data);
                    break;
            }
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
            'type' => $type,
            'data' => $data,
            'status' => 'pending',
        ]);
    }



    /**
     * send push notification to user using Firebase.
     */
    protected function sendPushNotification(User $user, array $data): void
    {
       
        if ($user->fcm_token) {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(FirebaseNotification::create(
                    $data['title'] ?? 'Thông báo mới',
                    $data['message'] ?? 'Bạn có thông báo mới.'
                ))
                ->withData($data);

            app('firebase.messaging')->send($message);
        }
    }
}
