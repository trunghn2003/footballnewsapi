<?php

namespace App\Console\Commands;

use App\Jobs\SyncFixtureJob;
use App\Services\FixtureService;
use Illuminate\Console\Command;

class SyncFixtureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:fixtures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync fixtures from external API';
    protected $fixtureService;
    public function __construct(FixtureService $fixtureService)
    {
        parent::__construct();
        $this->fixtureService = $fixtureService;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new SyncFixtureJob($this->fixtureService))->handle();

        $this->info('Fixtures have been synced successfully.');
        return Command::SUCCESS;
    }
}
