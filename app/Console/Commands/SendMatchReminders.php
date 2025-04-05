<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging\CloudMessage;

class SendMatchReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:match-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders 30 minutes before matches start';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(NotificationService $notificationService)
    {

        $nowUtc = Carbon::now('UTC');
        $laterUtc = (clone $nowUtc)->addHours(2);

        $matches = Fixture::where('utc_date', '>=', $nowUtc)
                        ->where('utc_date', '<=', $laterUtc)
                        ->get();
        //  dd($matches);

        foreach ($matches as $match) {
            $users = $this->getUsersToNotify($match);
            foreach ($users as $user) {
                $message = "Sap diễn ra: {$match->homeTeam->short_name} vs {$match->awayTeam->short_name}";
                $notificationService->send(
                    $user,
                    'match_reminder',
                    [
                        // 'title' => 'Nhắc nhở trận đấu',
                        'message' => $message,
                        'match_time' => $match->uct_date,
                        'location' => "chua co"
                    ],
                    ['push']
                );
            }
        }
        \Log::info('Match reminders sent successfully!' . Carbon::now() . 'team' . $match->homeTeam->short_name . ' vs ' . $match->awayTeam->short_name); ;

        $this->info('Match reminders sent successfully!');
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
