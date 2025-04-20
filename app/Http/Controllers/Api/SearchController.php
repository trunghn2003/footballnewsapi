<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Competition;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    private const MAX_LEVENSHTEIN_DISTANCE = 3;

    public function search(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 10);

        if (empty($query)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search query is required'
            ], 400);
        }

        try {
            $result = [];
            switch ($type) {
                case 'team':
                    $result = $this->searchTeam($query, 1);
                    break;
                case 'competition':
                    $result = $this->searchCompetition($query, 1);
                    break;
                case 'news':
                    $result = $this->searchNews($query, 3);
                    break;
                default:
                    $result = [
                        'teams' => $this->searchTeam($query, 1),
                        'competitions' => $this->searchCompetition($query, 1)
                    ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while searching',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function normalize($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/u', '', $text);
        return $text;
    }

    private function calculateSimilarity($input, $target)
    {
        $inputNorm = $this->normalize($input);
        $targetNorm = $this->normalize($target);
        similar_text($inputNorm, $targetNorm, $percent);
        return $percent;
    }

    private function searchTeam($query, $limit)
    {
        $teams = Team::where('name', 'LIKE', "%{$query}%")
            ->orWhere('tla', 'LIKE', "%{$query}%")
            ->orWhere('short_name', 'LIKE', "%{$query}%")
            ->get();

        if ($teams->isEmpty()) {
            $allTeams = Team::all();
            $fuzzyMatches = collect();

            foreach ($allTeams as $team) {
                $similarity = max(
                    $this->calculateSimilarity($query, $team->name),
                    $this->calculateSimilarity($query, $team->short_name),
                    $this->calculateSimilarity($query, $team->tla)
                );

                if ($similarity > 60) {
                    $team->similarity_score = $similarity;
                    $fuzzyMatches->push($team);
                }
            }

            $teams = $fuzzyMatches->sortByDesc('similarity_score')->take(1);
        }

        if ($teams->isEmpty()) {
            return null;
        }

        return $teams->map(function ($team) {
            $competitions = $team->competitions()->with(['currentSeason'])->get()->map(function ($competition) {
                return [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'code' => $competition->code,
                    'type' => $competition->type,
                    'emblem' => $competition->emblem,
                    'current_season' => $competition->currentSeason ? [
                        'id' => $competition->currentSeason->id,
                        'name' => $competition->currentSeason->name,
                        'start_date' => $competition->currentSeason->startDate,
                        'end_date' => $competition->currentSeason->endDate,
                    ] : null
                ];
            });

            $news = News::whereHas('teams', function ($query) use ($team) {
                $query->where('teams.id', $team->id);
            })->orderBy('created_at', 'desc')->take(3)->get()->map(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'summary' => Str::limit($news->content, 150),
                    'thumbnail' => $news->thumbnail,
                    'source' => $news->source,
                    'created_at' => $news->created_at->format('Y-m-d H:i:s')
                ];
            });

            return [
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'tla' => $team->tla,
                    'short_name' => $team->short_name,
                    'crest' => $team->crest,
                    'founded' => $team->founded,
                    'venue' => $team->venue,
                    'similarity' => isset($team->similarity_score) ? number_format($team->similarity_score, 1) . '%' : '100%'
                ],
                'competitions' => $competitions,
                'latest_news' => $news
            ];
        })->values();
    }

    private function searchCompetition($query, $limit)
    {
        $competitions = Competition::where('name', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->get();

        if ($competitions->isEmpty()) {
            $allCompetitions = Competition::all();
            $fuzzyMatches = collect();

            foreach ($allCompetitions as $competition) {
                $similarity = max(
                    $this->calculateSimilarity($query, $competition->name),
                    $this->calculateSimilarity($query, $competition->code)
                );

                if ($similarity > 60) {
                    $competition->similarity_score = $similarity;
                    $fuzzyMatches->push($competition);
                }
            }

            $competitions = $fuzzyMatches->sortByDesc('similarity_score')->take($limit);
        }

        if ($competitions->isEmpty()) {
            return null;
        }

        return $competitions->map(function ($competition) {


            $news = News::where('competition_id', $competition->id)->orderBy('created_at', 'desc')->take(3)->get()->map(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'summary' => Str::limit($news->content, 150),
                    'thumbnail' => $news->thumbnail,
                    'source' => $news->source,
                    'created_at' => $news->created_at->format('Y-m-d H:i:s')
                ];
            });

            return [
                'competition' => [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'code' => $competition->code,
                    'type' => $competition->type,
                    'emblem' => $competition->emblem,
                    'current_season' => $competition->currentSeason ? [
                        'id' => $competition->currentSeason->id,
                        'name' => $competition->currentSeason->name,
                        'start_date' => $competition->currentSeason->startDate,
                        'end_date' => $competition->currentSeason->endDate,
                    ] : null,
                    'similarity' => isset($competition->similarity_score) ? number_format($competition->similarity_score, 1) . '%' : '100%'
                ],
                'latest_news' => $news
            ];
        })->values();
    }
    private function searchNews($query, $limit)
    {
        $news = News::where('title', 'LIKE', "%{$query}%")
            // ->orWhere('content', 'LIKE', "%{$query}%")
            ->take($limit)
            ->get();

        if ($news->isEmpty()) {
            $allNews = News::all();
            $fuzzyMatches = collect();

            foreach ($allNews as $item) {
                $titleSimilarity = $this->calculateSimilarity($query, $item->title);
                $contentSimilarity = $this->calculateSimilarity($query, $item->content);
                $maxSimilarity = max($titleSimilarity, $contentSimilarity);

                if ($maxSimilarity > 70) {
                    $item->similarity_score = $maxSimilarity;
                    $fuzzyMatches->push($item);
                }
            }

            $news = $fuzzyMatches->sortByDesc('similarity_score')->take($limit);
        }

        return $news->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'summary' => Str::limit($item->content, 150),
                'thumbnail' => $item->thumbnail,
                'source' => $item->source,
                'created_at' => $item->created_at->format('Y-m-d H:i:s')
            ];
        })->values();
    }
}
