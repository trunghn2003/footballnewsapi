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
            $token = "dlbQZwZhy84p2bziZX8lSo:APA91bHmQB_0smAYlN0sNpxuiEHTSvEyikmqddk2gt197TOC3aJfoa2FzZOAp30zS_BmuKwTbjhM_oBVWxtXIbWATzuzAUpvG10tisFRqrWbqxLbD8Lc3ng";
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
