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
        $now = Carbon::now();

        $matches = Fixture::where('utc_date', '>=', $now)
                       ->where('utc_date', '<=', $now->addMinutes(30))
                       ->get();

        foreach ($matches as $match) {
            $users = $this->getUsersToNotify($match);
            foreach ($users as $user) {
                $notificationService->send(
                    $user,
                    'match_reminder',
                    [
                        // 'title' => 'Nhắc nhở trận đấu',
                        'message' => "Sắp diễn ra: {$match->homeTeam->short_name} vs {$match->awayTeam->short_name}",
                        'match_time' => $match->start_time->format('H:i'),
                        'location' => "chua co"
                    ],
                    ['push']
                );
            }
        }
        \Log::info('Match reminders sent successfully!');

        $this->info('Match reminders sent successfully!');
    }

    /**
     * Get users to notify based on their favorite teams.
     */
    protected function getUsersToNotify(Fixture $match)
    {
        return User::whereJsonContains('favorite_teams', $match->homeTeam->id)
                  ->orWhereJsonContains('favorite_teams', $match->awayTeam->id)
                  ->get();
    }
}
