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
{    public function sendNotification($token, $title, $body, $data = [])
    {
        // Check notification preferences if user_id is provided
        if (isset($data['user_id'])) {
            $user = \App\Models\User::find($data['user_id']);
            // dd(($user));
            if ($user && $user->notification_pref) {
                $prefs = json_decode($user->notification_pref, true);
                $type = $data['type'] ?? 'default';
                // dd($type);
                // Check notification type settings
                switch ($type) {
                    case 'match_score':
                        if (!($prefs['settings']['match_score'] ?? true)) {
                            return false;
                        }
                        break;
                    case 'team_news':
                        if (!($prefs['settings']['team_news'] ?? true)) {
                            return false;
                        }
                        break;
                    case 'match_reminder':
                        if (!($prefs['settings']['match_reminders'] ?? true)) {
                            return false;
                        }
                        break;
                    case 'competition_news':
                        if (!($prefs['settings']['competition_news'] ?? true)) {
                            return false;
                        }
                        break;
                }
            }
        }

        $fcmurl = "https://fcm.googleapis.com/v1/projects/footbackapi/messages:send";

        // Convert all data values to strings for FCM
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = (string) $value;
        }

        $notification = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $stringData,
            "token" => $token
        ];
        // dump($notification);

        try {
            $response  = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($fcmurl, ['message' => $notification]);
            // dd($response->json());

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
            // dd($response->json());

            return $response->json();
        } catch (Exception $e) {
            Log::info('Error in sending notification: ' . $e->getMessage());
            // return response()->json(['error' => 'Failed to send notification'], 500);
            return false;
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
