<?php

namespace App\Repositories;

use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsRepository
{
    protected $model;

    public function __construct(News $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {

        $existingNews = $this->model->where('title', $data['title'])->first();
        if ($existingNews) {
            return $existingNews;
        }

        DB::beginTransaction();
        try {
            $news = new News();
            $news->title = $data['title'];
            $news->content = $data['content'];
            $news->source = $data['source']['name'] ?? null;
            $news->thumbnail = $data['urlToImage'] ?? null;
            $news->published_at = $data['publishedAt'];
            $news->competition_id = $data['competition_id'] ?? null;
            $news->save();

            DB::commit();
            return $news;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving news article: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAllNews()
    {
        return $this->model->with(['comments', 'teams'])->get();
    }

    public function getNewsById($newsId)
    {
        return $this->model->with(['comments', 'teams'])->findOrFail($newsId);
    }

    public function updateNews($newsId, array $data)
    {
        $news = $this->model->findOrFail($newsId);
        $news->update($data);
        return $news;
    }

    public function deleteNews($newsId)
    {
        $news = $this->model->findOrFail($newsId);
        return $news->delete();
    }

    public function getNewsByTeam($teamId)
    {
        return $this->model->whereHas('teams', function ($query) use ($teamId) {
            $query->where('teams.id', $teamId);
        })->get();
    }

    public function getLatestNews($perPage = 10, $page = 1, $filters = [])
    {
        $query = $this->model->query();

        // Apply existing filters
        if (isset($filters['competition_id'])) {
            $query->where('competition_id', $filters['competition_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('published_at', '<=', $filters['date_to']);
        }

        if (isset($filters['team_id'])) {
            $query->whereHas('teams', function ($q) use ($filters) {
                $q->where('teams.id', $filters['team_id']);
            });
        }

        if (isset($filters['team_name'])) {
            $query->whereHas('teams', function ($q) use ($filters) {
                $q->where('teams.name', 'like', '%' . $filters['team_name'] . '%');
            });
        }

        if (isset($filters['sortBy'])) {
            if ($filters['sortBy'] === 'comment_count') {
                $query->withCount('comments')
                      ->orderBy('comments_count', $filters['sortOrder'] ?? 'desc');
            } else {
                $query->orderBy('published_at', $filters['sortOrder'] ?? 'desc');
            }
        } else {
            $query->orderBy('published_at', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
