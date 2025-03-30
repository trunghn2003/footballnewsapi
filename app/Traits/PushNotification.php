<?php
namespace App\Traits;

use Exception;
use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Http;

// use GPBMetadata\Google\Api\Http;

trait PushNotification
{
    public function sendNotification($token, $title, $body, $data = [])
    {
        $fcmurl = "https://fcm.googleapis.com/v1/projects/footbackapi/messages:send";
        $notification = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
            "token" => $token
        ];
        try {
            $response  = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($fcmurl, ['message' => $notification]);
            return $response->json();
        } catch(Exception $e){
            \Log::info('Error in sending notification: ' . $e->getMessage());
            // return response()->json(['error' => 'Failed to send notification'], 500);
            return false;
        }
    }
    private function getAccessToken()
    {
        $keyPath = config('services.firebase.key_path');
        putenv('GOOGLE_APPLICATION_CREDENTIALS='. $keyPath);
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = ApplicationDefaultCredentials::getCredentials($scopes);
        $token = $credentials->fetchAuthToken()['access_token'];
        return $token ?? null;

    }
}
