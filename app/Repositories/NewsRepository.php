<?php

namespace App\Repositories;

use App\Models\News;

class NewsRepository
{
    protected $news;

    public function __construct(News $news)
    {
        $this->news = $news;
    }

    public function getAllNews()
    {
        return $this->news->with(['comments', 'teams'])->get();
    }

    public function getNewsById($newsId)
    {
        return $this->news->with(['comments', 'teams'])->findOrFail($newsId);
    }

    public function createNews(array $data)
    {
        return $this->news->create($data);
    }

    public function updateNews($newsId, array $data)
    {
        $news = $this->news->findOrFail($newsId);
        $news->update($data);
        return $news;
    }

    public function deleteNews($newsId)
    {
        $news = $this->news->findOrFail($newsId);
        return $news->delete();
    }

    public function getNewsByTeam($teamId)
    {
        return $this->news->whereHas('teams', function ($query) use ($teamId) {
            $query->where('teams.id', $teamId);
        })->get();
    }
}
