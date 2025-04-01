<?php

namespace App\Repositories;

use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsRepository implements NewsRepositoryInterface
{
    protected $model;

    public function __construct(News $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
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
}
