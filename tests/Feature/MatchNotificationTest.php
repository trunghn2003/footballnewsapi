<?php

namespace Tests\Feature;

use App\Models\Fixture;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchNotificationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_send_notification_for_match_30_minutes_ahead()
    {
        // Giả lập thời gian hiện tại
        DB::beginTransaction();
        $now = now();
        try {

            // Tạo một trận đấu giả lập diễn ra sau 30 phút
            $match = Fixture::factory()->create([
                'id' => 1,
                'utc_date'     => $now->copy()->addMinutes(30),
                'competition_id' => 2001,
                'season_id'      => 2350,
                'status'         => 'TIMED',
                'last_updated'   => $now,
                // 'home_team_id'   => 'TeamA',
                // 'away_team_id'   => 'TeamB',
                // 'team_a' => 'Team A',
                // 'team_b' => 'Team B',
            ]);

            // Chạy logic kiểm tra thời gian
            $matchStartTime = $match->utc_date;
            $timeDiffInMinutes = $matchStartTime->diffInMinutes($now);

            // Nếu còn 30 phút, tạo thông báo
            if ($timeDiffInMinutes <= 30) {
                Notification::create([
                    'id' => 1,
                    // 'title' => 'Nhắc nhở trận đấu',
                    'message' => "Trận đấu  sẽ diễn ra trong 30 phút!",
                    'match_id' => $match->id,
                    'user_id' => 1,
                    'type' => 'match_reminders',
                ]);
            }
            DB::commit();



            // $this->assertDatabaseHas('notifications', [
            //     // 'title' => 'Nhắc nhở trận đấu',
            //     'message' => "Trận đấu Team A vs Team B sẽ diễn ra trong 30 phút!",
            //     'match_id' => $match->id,
            // ]);

        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            DB::rollback();
            $this->fail("Test failed with exception: " . $e->getMessage());
        }
    }
}
