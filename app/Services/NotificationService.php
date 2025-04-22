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
        // Check notification preferences before sending
        if (isset($data['entity_id']) && !$this->shouldNotifyUser($user->id, $type, $data['entity_id'])) {
            return false;
        }

        // Create notification record
        $notification = $this->createNotification($user, $type, $data);

        // Send push notification if FCM token exists
        if (in_array('push', $channels) && $user->fcm_token) {
            $this->sendNotification(
                $user->fcm_token,
                $data['title'] ?? $type,
                $data['message'] ?? '',
                array_merge($data, [
                    'type' => $type,
                    'notification_id' => $notification->id
                ])
            );
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

   public function getNotificationsByUserId($perPage = 10)
{
    // Fetch notifications from repository (assumed to return a paginated query)
    $result = $this->notificationRepository->getNotificationsByUser($perPage);

    // Transform and group notifications
    $notifications = $result->getCollection()->map(function ($notification) {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'created_at' => $notification->created_at,
            'data' => $notification->data,
            'is_read' => (bool) $notification->is_read
        ];
    })->sortByDesc('created_at') // Sort by newest first
      ->groupBy('is_read'); // Group by read/unread

    return [
        'notifications' => [
            'unread' => $notifications[false] ?? collect([]), // Unread notifications
            'read' => $notifications[true] ?? collect([]),    // Read notifications
        ],
        'total' => $result->total(),
        'current_page' => $result->currentPage(),
        'last_page' => $result->lastPage(),
        'per_page' => $result->perPage(),
    ];
}
    public function markAsRead($notificationId)
    {
        return $this->notificationRepository->markAsRead($notificationId);
    }

    public function updateNotificationPreferences($userId, array $preferences)
    {
        $user = User::findOrFail($userId);

        $notificationPref = [            'settings' => [
                'team_news' => (bool)($preferences['team_news'] ?? true),
                'match_reminders' => (bool)($preferences['match_reminders'] ?? true),
                'competition_news' => (bool)($preferences['competition_news'] ?? true),
                'match_score' => (bool)($preferences['match_score'] ?? true)
            ]
        ];

        $user->notification_pref = json_encode($notificationPref);
        $user->save();

        return $notificationPref;
    }

    public function shouldNotifyUser($userId, $type, $entityId)
    {
        $user = User::find($userId);
        if (!$user || !$user->notification_pref) {
            return false;
        }

        $prefs = json_decode($user->notification_pref, true);        switch ($type) {
            case 'team_news':
                return isset($prefs['settings']['team_news'])
                    && $prefs['settings']['team_news'];

            case 'match_reminder':
                return isset($prefs['settings']['match_reminders'])
                    && $prefs['settings']['match_reminders'];            case 'competition_news':
                return isset($prefs['settings']['competition_news'])
                    && $prefs['settings']['competition_news'];

            case 'match_score':
                return isset($prefs['settings']['match_score'])
                    && $prefs['settings']['match_score'];

            default:
                return false;
        }
    }
}
