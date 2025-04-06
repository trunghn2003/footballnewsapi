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
