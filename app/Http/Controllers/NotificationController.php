<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Request;

class NotificationController extends Controller
{
    use PushNotification;
    public function sendPushNotification(Request $request)
    {
        try {
            $users = User::all();
            $tokens = [];
            foreach ($users as $user) {
                if ($user->fcm_token) {
                    $tokens[] = $user->fcm_token;
                }
            }
            $title = 'Test';
            $body = 'Test body';
            $data = [
                'key1' => 'value1',
                'key2' => 'value2',
            ];
            if (!$tokens) {
                return response()->json(['error' => 'Token is required'], 400);
            }

            foreach ($tokens as $token) {
                $response = $this->sendNotification($token, $title, $body, $data);
            }
            return response()->json(['message' => 'Notification sent successfully', 'response' => $response], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send notification: ' . $e->getMessage()], 500);
        }
    }
}
