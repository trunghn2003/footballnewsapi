<?php

namespace App\Console\Commands;

use App\Services\StandingService;
use Illuminate\Console\Command;

class SyncStandingsCommand extends Command
{
    protected $signature = 'sync:standings';
    protected $description = 'Sync standings data from football API';

    protected $standingService;

    public function __construct(StandingService $standingService)
    {
        parent::__construct();
        $this->standingService = $standingService;
    }

    public function handle()
    {
        $this->info('Starting standings synchronization...');

        try {
            $this->standingService->storeStandingsFromApi();
            $this->info('Standings synchronization completed successfully.');
        } catch (\Exception $e) {
            $this->error('Error syncing standings: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
