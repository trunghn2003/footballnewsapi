<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    protected $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function scrapeArticles($competitionId)
    {
        try {
            $newsArticles = $this->newsService->fetchNewsFromApi($competitionId);
            $this->newsService->storeNewsFromApi($newsArticles, $competitionId);
            
            return response()->json([
                'message' => 'News articles fetched and saved successfully!',
                'count' => count($newsArticles)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching or saving news articles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
