<?php

namespace App\Console\Commands;

use App\Jobs\RawNewsJob;
use App\Jobs\SyncNewsJob;
use App\Services\NewsService;
use Illuminate\Console\Command;

class SyncNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync news articles from external API';

    protected $newsService;

    public function __construct(NewsService $newsService)
    {
        parent::__construct();
        $this->newsService = $newsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new RawNewsJob($this->newsService))->handle();

        $this->info('News articles have been synced successfully.');
        return Command::SUCCESS;
    }
}
