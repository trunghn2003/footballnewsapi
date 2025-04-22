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
use Illuminate\Support\Facades\Log;

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

        $nowUtc = Carbon::now('UTC');
        $nextUtc = (clone $nowUtc)->addHour(168);

        // Get all upcoming matches within 1 hour
        $matches = Fixture::with(['homeTeam', 'awayTeam'])
            ->where('utc_date', '>=', $nowUtc)
            ->where('utc_date', '<=', $nextUtc)
            ->orderBy('utc_date', 'asc')
            ->get();

        foreach ($matches as $match) {
            if (!$match || !$match->homeTeam || !$match->awayTeam) {
                Log::warning("Skipping match due to missing data", [
                    'match' => $match,
                    'homeTeam' => $match->homeTeam ?? null,
                    'awayTeam' => $match->awayTeam ?? null
                ]);
                continue;
            }

            $homeTeamId = $match->homeTeam->id;
            $awayTeamId = $match->awayTeam->id;

            // Tìm tất cả users yêu thích 1 trong 2 đội
            $users = User::whereJsonContains('favourite_teams', $homeTeamId)
                ->orWhereJsonContains('favourite_teams', $awayTeamId)
                ->get();

            foreach ($users as $user) {
                if (empty($user->fcm_token)) {
                    continue;
                }

                $matchTime = Carbon::createFromFormat('Y-m-d H:i:s', $match->utc_date, 'UTC')
                    ->setTimezone('Asia/Ho_Chi_Minh')
                    ->format('H:i d-m-Y');

                $homeTeamName = $match->homeTeam->short_name ?? 'Unknown Team';
                $awayTeamName = $match->awayTeam->short_name ?? 'Unknown Team';

                $title = "Nhắc nhở trận đấu của {$homeTeamName} vs {$awayTeamName} lúc {$matchTime}";
                $message = "Sắp diễn ra: {$homeTeamName} vs {$awayTeamName} lúc {$matchTime}";

                $logo = null;
                $favouriteTeams = json_decode($user->favourite_teams ?? '[]', true);
                if (in_array($homeTeamId, $favouriteTeams)) {
                    $logo = $match->homeTeam->crest;
                } elseif (in_array($awayTeamId, $favouriteTeams)) {
                    $logo = $match->awayTeam->crest;
                }

                $this->sendNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    [
                        'title' => $title,
                        'message' => $message,
                        'match_time' => $matchTime,
                        'type' => 'match_reminder',
                        'user_id' => $user->id,
                        'logo' => $logo ?? null,
                        'screen' => "/(drawer)/fixture/" . $match->id,
                    ]
                );
            }
        }

        $this->info('Match reminders sent successfully!');
    }


    /**
     * Get users to notify based on their favorite teams.
     */
    protected function getUsersToNotify(Fixture $match)
    {
       if(isset($match->homeTeam) && isset($match->awayTeam) && isset($match->homeTeam->id) && isset($match->awayTeam->id)) {
            return User::whereJsonContains('favourite_teams', $match->homeTeam->id)
                ->orWhereJsonContains('favourite_teams', $match->awayTeam->id)->where('id',1)
                ->get();
        }
        return [];
    }
}
