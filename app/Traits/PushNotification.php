<?php

namespace App\Traits;

use App\Models\Notification;
use Exception;
use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Http;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Log;

// use GPBMetadata\Google\Api\Http;

trait PushNotification
{
    public function sendNotification($token, $title, $body, $data = [])
    {
        // Kiểm tra cài đặt thông báo nếu user_id được cung cấp
        if (isset($data['user_id'])) {
            $user = \App\Models\User::find($data['user_id']);
            if ($user && $user->notification_pref) {
                $prefs = json_decode($user->notification_pref, true);
                $type = $data['type'] ?? 'default';

                // Kiểm tra chi tiết cài đặt thông báo
                if (!$this->shouldSendNotification($user, $type, $data)) {
                    return false;
                }
            }
        }

        $fcmurl = "https://fcm.googleapis.com/v1/projects/footbackapi/messages:send";

        // Convert all data values to strings for FCM
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = is_array($value) ? json_encode($value) : (string) $value;
        }

        $notification = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $stringData,
            "token" => $token
        ];

        try {
            $response  = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($fcmurl, ['message' => $notification]);

            // Only create notification if user_id exists in data
            if (isset($data['user_id'])) {
                Notification::create([
                    'user_id' => $data['user_id'],
                    'title' => $title,
                    'message' => $body,
                    'data' => ($data),
                    'type' => $data['type'] ?? 'default',
                    'is_read' => 0,
                ]);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::info('Error in sending notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem có nên gửi thông báo dựa trên cài đặt của người dùng
     *
     * @param \App\Models\User $user
     * @param string $type
     * @param array $data
     * @return bool
     */
    protected function shouldSendNotification($user, $type, $data = [])
    {
        if (!$user->notification_pref) {
            return true;
        }

        $prefs = json_decode($user->notification_pref, true);
        // dd(!$prefs['global_settings']);
        // Kiểm tra cài đặt toàn cục trước
        if (!isset($prefs['global_settings']) || !$this->isEnabledInGlobalSettings($prefs['global_settings'], $type)) {
            return false;
        }

        // Nếu là thông báo liên quan đến đội bóng
        if (in_array($type, ['team_news', 'match_reminders', 'match_score'])) {
            // Kiểm tra cài đặt đội bóng
            if (isset($data['team_ids']) && is_array($data['team_ids'])) {
                foreach ($data['team_ids'] as $teamId) {
                    // Nếu có cài đặt riêng cho đội này và bị tắt
                    if ($this->hasTeamSpecificSetting($prefs, $teamId) &&
                        !$this->isEnabledForTeam($prefs, $teamId, $type)) {
                        return false;
                    }
                }
            } elseif (isset($data['team_id'])) {
                $teamId = $data['team_id'];
                // Nếu có cài đặt riêng cho đội này và bị tắt
                if ($this->hasTeamSpecificSetting($prefs, $teamId) &&
                    !$this->isEnabledForTeam($prefs, $teamId, $type)) {
                    return false;
                }
            }
        }

        // Nếu là thông báo liên quan đến giải đấu
        if (in_array($type, ['competition_news', 'match_reminders', 'match_score'])) {
            // Kiểm tra cài đặt giải đấu
            if (isset($data['competition_id'])) {
                $competitionId = $data['competition_id'];
                // Nếu có cài đặt riêng cho giải đấu này và bị tắt
                if ($this->hasCompetitionSpecificSetting($prefs, $competitionId) &&
                    !$this->isEnabledForCompetition($prefs, $competitionId, $type)) {
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
    private function hasTeamSpecificSetting($prefs, $teamId)
    {
        return isset($prefs['team_settings']) && isset($prefs['team_settings'][$teamId]);
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
    private function hasCompetitionSpecificSetting($prefs, $competitionId)
    {
        return isset($prefs['competition_settings']) && isset($prefs['competition_settings'][$competitionId]);
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
            case 'pinned_match_reminder':
                return 'match_reminders';
            case 'competition_news':
                return 'competition_news';
            case 'match_score':
                return 'match_score';
            default:
                return '';
        }
    }

    private function getAccessToken()
    {
        $keyPath = config('services.firebase.key_path');
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyPath);
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = ApplicationDefaultCredentials::getCredentials($scopes);
        $token = $credentials->fetchAuthToken()['access_token'];
        return $token ?? null;
    }
}
