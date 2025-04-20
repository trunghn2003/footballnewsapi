<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Request;

class NotificationController extends Controller
{
    use PushNotification;
    public function sendPushNotification(Request $request)
    {
        $user = User::find(14);
        // dd(1);
        $title = "Match Reminder";
        $message = "Your match is starting soon!";
        $matchTime = "2023-10-01 15:00:00"; // Example match time
        $matchTime = date('Y-m-d H:i:s', strtotime($matchTime));
        $matchTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matchTime, 'UTC')
            ->setTimezone('Asia/Kolkata')
            ->format('Y-m-d H:i:s');

         $result = $this->sendNotification(
                $user->fcm_token,
                $title,
                $message,
                [
                    'title' => $title,
                    'message' => $message,
                    'match_time' => $matchTime,
                    'type' => 'match_reminder',
                    'user_id' => $user->id,
                    'logo' => $match->homeTeam->crest ?? null,
                ]
            );
            // dd(1);
           return response()->json([
                'message' => 'Notification sent successfully',
                'result' => $result,
            ]);
    }
}
