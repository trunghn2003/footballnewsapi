<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\StandingService;
use Illuminate\Support\Facades\Log;

class SyncStandingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(StandingService $standingService)
    {
        try {
            Log::info('Starting standings sync job');
            $standingService->storeStandingsFromApi();
            Log::info('Standings sync job completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in standings sync job: ' . $e->getMessage());
            throw $e;
        }
    }
}
