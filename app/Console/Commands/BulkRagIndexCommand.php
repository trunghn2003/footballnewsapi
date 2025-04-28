<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BulkIndexNewsJob;
use App\Jobs\BulkIndexTeamsJob;
use App\Jobs\BulkIndexCompetitionsJob;
use App\Jobs\BulkIndexFixturesJob;
use App\Jobs\BulkIndexSeasonsJob;

class BulkRagIndexCommand extends Command
{
    protected $signature = 'rag:index {type? : Type of data to index (news/teams/competitions/fixtures/seasons/all)}';
    protected $description = 'Bulk index data into RAG system';

    public function handle()
    {
        $type = $this->argument('type') ?? 'all';

        switch ($type) {
            case 'news':
                $this->info('Dispatching News indexing job...');
                BulkIndexNewsJob::dispatch();
                break;

            case 'teams':
                $this->info('Dispatching Teams indexing job...');
                BulkIndexTeamsJob::dispatch();
                break;

            case 'competitions':
                $this->info('Dispatching Competitions indexing job...');
                BulkIndexCompetitionsJob::dispatch();
                break;

            case 'fixtures':
                $this->info('Dispatching Fixtures indexing job...');
                BulkIndexFixturesJob::dispatch();
                break;

            case 'seasons':
                $this->info('Dispatching Seasons indexing job...');
                BulkIndexSeasonsJob::dispatch();
                break;

            case 'all':
                $this->info('Dispatching all indexing jobs...');
                BulkIndexNewsJob::dispatch();
                BulkIndexTeamsJob::dispatch();
                BulkIndexCompetitionsJob::dispatch();
                BulkIndexFixturesJob::dispatch();
                BulkIndexSeasonsJob::dispatch();
                break;

            default:
                $this->error('Invalid type. Allowed types: news, teams, competitions, fixtures, seasons, all');
                return 1;
        }

        $this->info('Jobs queued successfully!');
        return 0;
    }
}
