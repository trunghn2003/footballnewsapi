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
        if ($type == 'comment_reply') {
            return true;
        }
        if (!$user->notification_pref) {
            return true;
        }

        $prefs = json_decode($user->notification_pref, true);
        if (!isset($prefs['global_settings']) || !$this->isEnabledInGlobalSettings($prefs['global_settings'], $type)) {
            return false;
        }

        return true;
    }

    /**
     * Kiểm tra loại thông báo có được bật trong cài đặt toàn cục không
     */
    private function isEnabledInGlobalSettings($globalSettings, $type)
    {
        return isset($globalSettings[$type]) && $globalSettings[$type];
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
