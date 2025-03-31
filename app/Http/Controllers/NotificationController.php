<?php

namespace App\Http\Controllers;

use App\Traits\PushNotification;
use Illuminate\Support\Facades\Request;

class NotificationController extends Controller
{
    use PushNotification;
    public function sendPushNotification(Request $request)
    {
        try {
            $token = "cqCOAnAWGQohxcM2Y8nS1q:APA91bGbsxFBhhPGCyBI4hnnFA8QeaXZXoU8g3st8TDqgxqPqVA-b4C819Cf5ENT6QGARkq7cHYZKQ-tE0MekjziyHA9y_u4EtoCenAu4nsEtq1yVosNrn8";
            $title = 'Test';
            $body = 'Test body';
            $data = [
                'key1' => 'value1',
                'key2' => 'value2',
            ];
            if (!$token) {
                return response()->json(['error' => 'Token is required'], 400);
            }

            $response = $this->sendNotification($token, $title, $body, $data);
            return response()->json(['message' => 'Notification sent successfully', 'response' => $response], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send notification: ' . $e->getMessage()], 500);
        }
    }
}
