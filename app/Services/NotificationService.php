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
            $this->sendNotification($user->fcm_token, $data['title'] ?? $type, $data['message'] ?? '', array_merge($data, [
                    'type' => $type,
                    'notification_id' => $notification->id
                ]));
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
        ->groupBy('is_read');
// Group by read/unread

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

    /**
     * Cập nhật cài đặt thông báo của người dùng
     *
     * @param int $userId
     * @param array $preferences
     * @return array
     */
    public function updateNotificationPreferences($userId, array $preferences)
    {
        $user = User::findOrFail($userId);
// Lấy cài đặt hiện tại
        $currentPrefs = json_decode($user->notification_pref, true) ?: [
            'global_settings' => [
                'team_news' => true,
                'match_reminders' => true,
                'competition_news' => true,
                'match_score' => true
            ],
            'team_settings' => [],
            'competition_settings' => []
        ];
// Cập nhật cài đặt toàn cục
        if (isset($preferences['global_settings'])) {
            $currentPrefs['global_settings'] = array_merge($currentPrefs['global_settings'] ?? [], $preferences['global_settings']);
        }

        // Cài đặt cho từng đội cụ thể
        if (isset($preferences['team_settings'])) {
// Xử lý trường hợp team_settings chỉ là mảng số nguyên
            if (is_array($preferences['team_settings']) && count($preferences['team_settings']) > 0) {
                $teamSettings = [];
// Trường hợp 1: team_settings là mảng đối tượng đúng chuẩn
                if (isset($preferences['team_settings'][0]) && is_array($preferences['team_settings'][0])) {
                    foreach ($preferences['team_settings'] as $setting) {
                        if (isset($setting['team_id'])) {
                            $teamId = $setting['team_id'];
                            $teamSettings[$teamId] = [
                                'team_id' => $teamId,
                                'team_news' => $setting['team_news'] ?? true,
                                'match_reminders' => $setting['match_reminders'] ?? true,
                                'match_score' => $setting['match_score'] ?? true
                            ];
                        }
                    }
                }
                // Trường hợp 2: team_settings chỉ là mảng ID
                else {
                    foreach ($preferences['team_settings'] as $teamId) {
                        if (is_numeric($teamId)) {
                            $teamSettings[$teamId] = [
                                'team_id' => $teamId,
                                'team_news' => true,
                                'match_reminders' => true,
                                'match_score' => true
                            ];
                        }
                    }
                }

                $currentPrefs['team_settings'] = $teamSettings;
            }
        }

        // Cài đặt cho từng giải đấu cụ thể
        if (isset($preferences['competition_settings'])) {
// Xử lý tương tự như team_settings
            if (is_array($preferences['competition_settings']) && count($preferences['competition_settings']) > 0) {
                $competitionSettings = [];
// Trường hợp 1: mảng đối tượng
                if (isset($preferences['competition_settings'][0]) && is_array($preferences['competition_settings'][0])) {
                    foreach ($preferences['competition_settings'] as $setting) {
                        if (isset($setting['competition_id'])) {
                            $competitionId = $setting['competition_id'];
                            $competitionSettings[$competitionId] = [
                                'competition_id' => $competitionId,
                                'competition_news' => $setting['competition_news'] ?? true,
                                'match_reminders' => $setting['match_reminders'] ?? true,
                                'match_score' => $setting['match_score'] ?? true
                            ];
                        }
                    }
                }
                // Trường hợp 2: chỉ là mảng ID
                else {
                    foreach ($preferences['competition_settings'] as $competitionId) {
                        if (is_numeric($competitionId)) {
                            $competitionSettings[$competitionId] = [
                                'competition_id' => $competitionId,
                                'competition_news' => true,
                                'match_reminders' => true,
                                'match_score' => true
                            ];
                        }
                    }
                }

                $currentPrefs['competition_settings'] = $competitionSettings;
            }
        }

        // Lưu cài đặt thông báo
        $user->notification_pref = json_encode($currentPrefs);
        $user->save();
        return $currentPrefs;
    }

    /**
     * Kiểm tra xem có nên gửi thông báo cho một người dùng dựa trên cài đặt
     *
     * @param int $userId
     * @param string $type
     * @param int|null $entityId
     * @param array|null $metadata
     * @return bool
     */
    public function shouldNotifyUser($userId, $type, $entityId = null, $metadata = null)
    {
        $user = User::find($userId);
        if (!$user || !$user->notification_pref) {
            return true;
        // Mặc định là gửi thông báo nếu không có cài đặt
        }

        $prefs = json_decode($user->notification_pref, true);
// Kiểm tra xem thông báo toàn cục có bật không
        if (!isset($prefs['global_settings']) || !$this->isEnabledInGlobalSettings($prefs['global_settings'], $type)) {
            return false;
        }

        // Kiểm tra cài đặt cụ thể cho đội bóng
        if ($type === 'team_news' || $type === 'match_reminders' || $type === 'match_score') {
            if (isset($metadata['team_id'])) {
                $teamId = $metadata['team_id'];
                if (
                    $this->hasTeamSpecificSetting($prefs, $teamId, $type) &&
                    !$this->isEnabledForTeam($prefs, $teamId, $type)
                ) {
                    return false;
                }
            }
        }

        // Kiểm tra cài đặt cụ thể cho giải đấu
        if ($type === 'competition_news' || $type === 'match_reminders' || $type === 'match_score') {
            if (isset($metadata['competition_id'])) {
                $competitionId = $metadata['competition_id'];
                if (
                    $this->hasCompetitionSpecificSetting($prefs, $competitionId, $type) &&
                    !$this->isEnabledForCompetition($prefs, $competitionId, $type)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Kiểm tra loại thông báo có được bật trong cài đặt toàn cục không
     */
    private function isEnabledInGlobalSettings($globalSettings, $type)
    {
        $settingKey = $this->getSettingKeyByType($type);
        return isset($globalSettings[$settingKey]) && $globalSettings[$settingKey];
    }

    /**
     * Kiểm tra xem có cài đặt cụ thể cho đội bóng không
     */
    private function hasTeamSpecificSetting($prefs, $teamId, $type)
    {
        return isset($prefs['team_settings'][$teamId]);
    }

    /**
     * Kiểm tra cài đặt thông báo cho đội bóng cụ thể
     */
    private function isEnabledForTeam($prefs, $teamId, $type)
    {
        $settingKey = $this->getSettingKeyByType($type);
        return isset($prefs['team_settings'][$teamId][$settingKey]) &&
               $prefs['team_settings'][$teamId][$settingKey];
    }

    /**
     * Kiểm tra xem có cài đặt cụ thể cho giải đấu không
     */
    private function hasCompetitionSpecificSetting($prefs, $competitionId, $type)
    {
        return isset($prefs['competition_settings'][$competitionId]);
    }

    /**
     * Kiểm tra cài đặt thông báo cho giải đấu cụ thể
     */
    private function isEnabledForCompetition($prefs, $competitionId, $type)
    {
        $settingKey = $this->getSettingKeyByType($type);
        return isset($prefs['competition_settings'][$competitionId][$settingKey]) &&
               $prefs['competition_settings'][$competitionId][$settingKey];
    }

    /**
     * Chuyển đổi loại thông báo thành tên cài đặt tương ứng
     */
    private function getSettingKeyByType($type)
    {
        switch ($type) {
            case 'team_news':
                return 'team_news';
            case 'match_reminders':
                return 'match_reminders';
            case 'competition_news':
                return 'competition_news';
            case 'match_score':
                return 'match_score';
            default:
                return '';
        }
    }
}
