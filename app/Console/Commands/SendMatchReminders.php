<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\User;
use App\Repositories\NotificationRepository;
use App\Traits\PushNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery\Matcher\Not;

class SendMatchReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:match-reminders';
    protected $noficationRepository;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders 30 minutes before matches start';
    use PushNotification;
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(NotificationRepository $noficationRepository)
    {
        $this->noficationRepository = $noficationRepository;
    {

        $nowUtc = Carbon::now('UTC');
        $laterUtc = (clone $nowUtc)->addHours(2);

        $matches = Fixture::where('utc_date', '>=', $nowUtc)
                        ->where('utc_date', '<=', $laterUtc)
                        ->get();
        // $matches = Fixture::where('id', 498904)->get();

        foreach ($matches as $match) {
            $users = $this->getUsersToNotify($match);

            foreach ($users as $user) {
                if (empty($user->fcm_token)) {
                    continue;
                }
                $matchTime = Carbon::createFromFormat('Y-m-d H:i:s', $match->utc_date, 'UTC')
                    ->setTimezone('Asia/Ho_Chi_Minh')
                    ->format('H:i d-m-Y');
                $message = "Sap diễn ra: {$match->homeTeam->short_name} vs
                {$match->awayTeam->short_name} lúc {$matchTime}";
                $title = "Nhắc nhở trận đấu trận đấu của {$match->homeTeam->short_name} vs {$match->awayTeam->short_name} lúc {$matchTime}";
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
                        // 'location' => "chua co"
                    ]
                );
                // dd($result);
            }
        }
        \Log::info('Match reminders sent successfully!' . Carbon::now() . 'team' . $match->homeTeam->short_name . ' vs ' . $match->awayTeam->short_name); ;

        $this->info('Match reminders sent successfully!');
    }
}

    /**
     * Get users to notify based on their favorite teams.
     */
    protected function getUsersToNotify(Fixture $match)
    {
        return User::whereJsonContains('favourite_teams', $match->homeTeam->id)
                  ->orWhereJsonContains('favourite_teams', $match->awayTeam->id)
                  ->get();
    }
}
