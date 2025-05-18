<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ApiResponseTrait;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Request;
use App\Http\Requests\UpdateNotificationPreferencesRequest;

class NotificationController extends Controller
{
    use PushNotification;
    use ApiResponseTrait;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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
                    'type' => 'match_reminders',
                    'user_id' => $user->id,
                    'logo' => $match->homeTeam->crest ?? null,
                ]
         );
           return response()->json([
                'message' => 'Notification sent successfully',
                'result' => $result,
            ]);
    }

    public function getNotifications()
    {
        $result = $this->notificationService->getNotificationsByUserId(10);
        return $this->successResponse($result, 'Notifications retrieved successfully');
    }

    public function markAsRead($id)
    {
        $result = $this->notificationService->markAsRead($id);
        return $this->successResponse($result, 'Notification marked as read successfully');
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request)
    {
        try {
            $preferences = $this->notificationService->updateNotificationPreferences(
                auth()->id(),
                $request->validated()
            );

            return $this->successResponse($preferences, 'Notification preferences updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update notification preferences', 500);
        }
    }

    public function getPreferences()
    {
        try {
            $user = auth()->user();
            $preferences = json_decode($user->notification_pref, true) ?? [
                'global_settings' => [
                    'team_news' => true,
                    'match_reminders' => true,
                    'competition_news' => true,
                    'match_score' => true
                ],
                'team_settings' => [],
                'competition_settings' => []
            ];

            return $this->successResponse($preferences);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get notification preferences', 500);
        }
    }
}
