<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    use ApiResponseTrait;

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

    public function getAllNews(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->page ?? 1;
            $filters = [
                'competition_id' => $request->input('competition_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'team_id' => $request->input('team_id'),
                'team_name' => $request->input('team_name'),
                'sortBy' => $request->input('sortBy', 'published_at'),
            ];

            $news = $this->newsService->getLatestNews($perPage, $page, $filters);

            return $this->successResponse($news);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getNewsById($id)
    {
        try {
            $news = $this->newsService->getNewsById($id);

            if (!$news) {
                return $this->errorResponse('News not found', 404);
            }

            return $this->successResponse($news);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    public function saveNews($id)
    {
        $result =  $this->newsService->saveNews($id, auth()->id());
        return $this->successResponse($result);
    }

    public function unsaveNews($id)
    {
        $result =  $this->newsService->unsaveNews($id, auth()->id());
        return $this->successResponse($result);
    }

    public function getSavedNews(Request $request)
    {
        $result =  $this->newsService->getSavedNews(
            auth()->id(),
            $request->input('per_page', 10),
            $request->page ?? 1
        );
        return $this->successResponse($result);
    }
}
