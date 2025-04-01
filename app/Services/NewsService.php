<?php

namespace App\Services;

use App\Repositories\NewsRepository;
use App\Models\Team;
use App\Repositories\TeamRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class NewsService
{
    protected $newsRepository;
    protected $teamRepository;
    protected $apiKey;
    protected $baseUrl = 'http://127.0.0.1:5000/api/scrape-articles';

    public function __construct(NewsRepository $newsRepository, TeamRepository $teamRepository)
    {
        $this->newsRepository = $newsRepository;
        $this->teamRepository = $teamRepository;
    }

    public function fetchNewsFromApi($competitionId)
    {
        $response = Http::get($this->baseUrl . '/' . $competitionId);
        // dd($response);
        try { 
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch news: ' . $response->body());
            }

            $data = $response->json();
            return $data['results'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching news: ' . $e->getMessage());
            throw $e;
        }
    }

    public function storeNewsFromApi(array $newsArticles, $competitionId)
    {
        DB::beginTransaction();
        try {
            foreach ($newsArticles as $article) {
                $article['competition_id'] = $competitionId;
                $news = $this->newsRepository->create($article);
                
                // Check for team names in article content
                $this->processTeamRelationships($news, $article);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            Log::error('Error in NewsService: ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }

    protected function processTeamRelationships($news, $article)
    {
        $content = strtolower($article['title'] . ' ' . $article['description']. ' ' .$article['content']);
        
        $teams = $this->teamRepository->findAll();
        
        foreach ($teams as $team) {
            $teamName = strtolower($team->name);
            $teamShortname = strtolower($team->short_name);
            if (strpos($content, $teamName) !== false || strpos($content, $teamShortname) !== false) {
                $news->teams()->attach($team->id);
            }
        }
    }

    public function getLatestNews($perPage = 10, $filters = [])
    {
        $result = $this->newsRepository->getLatestNews($perPage, $filters);
        return [
            'news' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total()
            ]
        ];
    }
} 