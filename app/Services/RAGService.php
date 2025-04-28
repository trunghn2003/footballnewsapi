<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\News;
use App\Models\Team;
use Illuminate\Support\Facades\Validator;

class RAGService
{
    protected $baseUrl;
    protected $maxRetries = 3;
    protected $timeout = 600; // 10 minutes for bulk operations
    protected $cacheTime = 3600; // 1 hour

    public function __construct()
    {
        $this->baseUrl = config('services.rag.url', 'http://localhost:8000');
        if (!$this->baseUrl) {
            throw new \Exception('RAG service URL not configured');
        }
    }

    protected function validateIndexData($data)
    {
        $validator = Validator::make($data, [
            'id' => 'required|string',
            'content' => 'required|string|min:10',
            'metadata' => 'required|array'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Invalid index data: ' . json_encode($validator->errors()));
        }

        return $data;
    }

    protected function makeRequest($endpoint, $data, $method = 'POST')
    {
        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'X-API-Key' => config('services.rag.api_key')
                    ])
                    ->$method($this->baseUrl . $endpoint, $data);

                if ($response->successful()) {
                    return $response;
                }

                Log::warning("RAG request failed (attempt " . ($attempt + 1) . "): " . $response->body());
                $attempt++;
                sleep(pow(2, $attempt)); // Exponential backoff

            } catch (\Exception $e) {
                Log::error("RAG request error (attempt " . ($attempt + 1) . "): " . $e->getMessage());
                $attempt++;
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }
                sleep(pow(2, $attempt));
            }
        }

        throw new \Exception('Max retries exceeded');
    }

    public function indexNewsArticle(News $news)
    {
        try {
            $data = [
                'id' => 'news_' . $news->id,
                'content' => $news->title . "\n" . $news->content,
                'metadata' => [
                    'type' => 'news',
                    'source' => $news->source,
                    'competition_id' => $news->competition_id,
                    'created_at' => $news->created_at->toIso8601String()
                ]
            ];

            $this->validateIndexData($data);

            $cacheKey = 'rag_index_news_' . $news->id;
            if (Cache::has($cacheKey)) {
                return true;
            }

            $response = $this->makeRequest('/index', $data);
            Cache::put($cacheKey, true, $this->cacheTime);

            return [
                'success' => true,
                'message' => 'News indexed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error indexing news: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to index news',
                'error' => $e->getMessage()
            ];
        }
    }

    public function indexTeamInfo(Team $team)
    {
        try {
            // Build rich content including competitions and recent results
            $content = "Team: {$team->name}\n";

            // Add competitions
            $competitions = $team->competitions()->with('currentSeason')->get();
            if ($competitions->isNotEmpty()) {
                $content .= "\nCompetitions:\n";
                foreach ($competitions as $competition) {
                    $content .= "- {$competition->name}";
                    if ($competition->currentSeason) {
                        $content .= " ({$competition->currentSeason->name})";
                    }
                    $content .= "\n";
                }
            }

            $data = [
                'id' => 'team_' . $team->id,
                'content' => $content,
                'metadata' => [
                    'type' => 'team',
                    'name' => $team->name,
                    'area_id' => $team->area_id,
                    'updated_at' => $team->updated_at->toIso8601String()
                ]
            ];

            $this->validateIndexData($data);

            $cacheKey = 'rag_index_team_' . $team->id;
            if (Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'message' => 'Team data retrieved from cache'
                ];
            }

            $response = $this->makeRequest('/index', $data);
            Cache::put($cacheKey, true, $this->cacheTime);

            return [
                'success' => true,
                'message' => 'Team indexed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error indexing team: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to index team',
                'error' => $e->getMessage()
            ];
        }
    }

    public function searchAndGenerateResponse($query, $type = null)
    {
        try {
            $validator = Validator::make([
                'query' => $query,
                'type' => $type
            ], [
                'query' => 'required|string|min:3|max:1000',
                'type' => 'nullable|string|in:news,team,competition'
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'Invalid query parameters',
                    'errors' => $validator->errors()
                ];
            }

            // Try cache first
            $cacheKey = 'rag_query_' . md5($query . ($type ?? ''));
            if ($cachedResponse = Cache::get($cacheKey)) {
                return [
                    'success' => true,
                    'data' => $cachedResponse,
                    'source' => 'cache'
                ];
            }

            $response = $this->makeRequest('/query', [
                'query' => $query,
                'n_results' => 3,
                'type' => $type,
                'language' => app()->getLocale() // Support multiple languages
            ]);

            $result = $response->json();

            // Cache the response
            Cache::put($cacheKey, $result, now()->addMinutes(30));

            return [
                'success' => true,
                'data' => [
                    'answer' => $result['answer'],
                    'sources' => $result['sources'] ?? [],
                    'confidence' => $result['confidence'] ?? null
                ],
                'source' => 'api'
            ];

        } catch (\Exception $e) {
            Log::error('Error in search and generate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process query',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function processBulkIndex($documents)
    {
        if (empty($documents)) {
            return [
                'success' => true,
                'message' => 'No documents to index',
                'count' => 0
            ];
        }

        try {
            $response = $this->makeRequest('/bulk_index', $documents);
            return [
                'success' => true,
                'message' => 'Bulk index completed successfully',
                'count' => count($documents)
            ];
        } catch (\Exception $e) {
            Log::error('Error in bulk indexing: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bulk index failed',
                'error' => $e->getMessage()
            ];
        }
    }

    public function bulkIndexNews()
    {
        $totalIndexed = 0;
        $failedBatches = 0;

        $result = News::chunk(100, function($articles) use (&$totalIndexed, &$failedBatches) {
            $documents = $articles->map(function($article) {
                return [
                    'id' => 'news_' . $article->id,
                    'content' => $article->title . "\n" . $article->content,
                    'metadata' => [
                        'type' => 'news',
                        'source' => $article->source,
                        'competition_id' => $article->competition_id,
                        'published_at' => $article->published_at?->toIso8601String(),
                        'teams' => $article->teams->pluck('id')->toArray()
                    ]
                ];
            })->toArray();

            $result = $this->processBulkIndex($documents);
            if ($result['success']) {
                $totalIndexed += $result['count'];
                // Cache the indexing status for each article
                foreach ($articles as $article) {
                    Cache::put('rag_index_news_' . $article->id, true, $this->cacheTime);
                }
            } else {
                $failedBatches++;
            }
        });

        return [
            'success' => $failedBatches === 0,
            'total_indexed' => $totalIndexed,
            'failed_batches' => $failedBatches
        ];
    }

    public function bulkIndexTeams()
    {
        $totalIndexed = 0;
        $failedBatches = 0;

        Team::with(['competitions.currentSeason'])->chunk(100, function($teams) use (&$totalIndexed, &$failedBatches) {
            $documents = $teams->map(function($team) {
                $content = "Team: {$team->name}\n";
                if ($team->description) {
                    $content .= "Description: {$team->description}\n";
                }
                if ($team->history) {
                    $content .= "History: {$team->history}\n";
                }

                // Add competitions
                if ($team->competitions->isNotEmpty()) {
                    $content .= "\nCompetitions:\n";
                    foreach ($team->competitions as $competition) {
                        $content .= "- {$competition->name}";
                        if ($competition->currentSeason) {
                            $content .= " ({$competition->currentSeason->name})";
                        }
                        $content .= "\n";
                    }
                }

                return [
                    'id' => 'team_' . $team->id,
                    'content' => $content,
                    'metadata' => [
                        'type' => 'team',
                        'name' => $team->name,
                        'area_id' => $team->area_id,
                        'competitions' => $team->competitions->pluck('id')->toArray(),
                        'updated_at' => $team->updated_at->toIso8601String()
                    ]
                ];
            })->toArray();

            $result = $this->processBulkIndex($documents);
            if ($result['success']) {
                $totalIndexed += $result['count'];
                // Cache the indexing status for each team
                foreach ($teams as $team) {
                    Cache::put('rag_index_team_' . $team->id, true, $this->cacheTime);
                }
            } else {
                $failedBatches++;
            }
        });

        return [
            'success' => $failedBatches === 0,
            'total_indexed' => $totalIndexed,
            'failed_batches' => $failedBatches
        ];
    }

    public function bulkIndexCompetitions()
    {
        $totalIndexed = 0;
        $failedBatches = 0;

        \App\Models\Competition::with(['currentSeason', 'area'])->chunk(100, function($competitions) use (&$totalIndexed, &$failedBatches) {
            $documents = $competitions->map(function($competition) {
                $content = "Competition: {$competition->name}\n";
                if ($competition->type) {
                    $content .= "Type: {$competition->type}\n";
                }
                if ($competition->area) {
                    $content .= "Area: {$competition->area->name}\n";
                }
                if ($competition->currentSeason) {
                    $content .= "Current Season: {$competition->currentSeason->name}\n";
                    $content .= "Start Date: {$competition->currentSeason->start_date}\n";
                    $content .= "End Date: {$competition->currentSeason->end_date}\n";
                }

                return [
                    'id' => 'competition_' . $competition->id,
                    'content' => $content,
                    'metadata' => [
                        'type' => 'competition',
                        'name' => $competition->name,
                        'area_id' => $competition->area_id,
                        'current_season_id' => $competition->current_season_id,
                        'updated_at' => $competition->updated_at->toIso8601String()
                    ]
                ];
            })->toArray();

            $result = $this->processBulkIndex($documents);
            if ($result['success']) {
                $totalIndexed += $result['count'];
                foreach ($competitions as $competition) {
                    Cache::put('rag_index_competition_' . $competition->id, true, $this->cacheTime);
                }
            } else {
                $failedBatches++;
            }
        });

        return [
            'success' => $failedBatches === 0,
            'total_indexed' => $totalIndexed,
            'failed_batches' => $failedBatches
        ];
    }

    public function bulkIndexFixtures()
    {
        $totalIndexed = 0;
        $failedBatches = 0;

        \App\Models\Fixture::with(['competition.currentSeason', 'homeTeam', 'awayTeam'])
            ->chunk(100, function($fixtures) use (&$totalIndexed, &$failedBatches) {
                $documents = $fixtures->map(function($fixture) {
                    $content = "Match: {$fixture->homeTeam->name} vs {$fixture->awayTeam->name}\n";
                    $content .= "Competition: {$fixture->competition->name}\n";
                    $content .= "Date: {$fixture->date}\n";
                    if ($fixture->status) {
                        $content .= "Status: {$fixture->status}\n";
                    }
                    if ($fixture->score) {
                        $content .= "Score: {$fixture->score->home_score} - {$fixture->score->away_score}\n";
                    }
                    if ($fixture->venue) {
                        $content .= "Venue: {$fixture->venue}\n";
                    }

                    return [
                        'id' => 'fixture_' . $fixture->id,
                        'content' => $content,
                        'metadata' => [
                            'type' => 'fixture',
                            'competition_id' => $fixture->competition_id,
                            'home_team_id' => $fixture->home_team_id,
                            'away_team_id' => $fixture->away_team_id,
                            'status' => $fixture->status,
                            'date' => $fixture->uct_date->toIso8601String(),
                        ]
                    ];
                })->toArray();

                $result = $this->processBulkIndex($documents);
                if ($result['success']) {
                    $totalIndexed += $result['count'];
                    foreach ($fixtures as $fixture) {
                        Cache::put('rag_index_fixture_' . $fixture->id, true, $this->cacheTime);
                    }
                } else {
                    $failedBatches++;
                }
            });

        return [
            'success' => $failedBatches === 0,
            'total_indexed' => $totalIndexed,
            'failed_batches' => $failedBatches
        ];
    }

    public function bulkIndexSeasons()
    {
        $totalIndexed = 0;
        $failedBatches = 0;

        \App\Models\Season::with(['competition'])->chunk(100, function($seasons) use (&$totalIndexed, &$failedBatches) {
            $documents = $seasons->map(function($season) {
                $content = "Season: {$season->name}\n";
                $content .= "Competition: {$season->competition->name}\n";
                $content .= "Start Date: {$season->start_date}\n";
                $content .= "End Date: {$season->end_date}\n";
                if ($season->current) {
                    $content .= "Status: Current Season\n";
                }

                return [
                    'id' => 'season_' . $season->id,
                    'content' => $content,
                    'metadata' => [
                        'type' => 'season',
                        'name' => $season->name,
                        'competition_id' => $season->competition_id,
                        'start_date' => $season->start_date->toIso8601String(),
                        'end_date' => $season->end_date->toIso8601String(),
                        'current' => $season->current
                    ]
                ];
            })->toArray();

            $result = $this->processBulkIndex($documents);
            if ($result['success']) {
                $totalIndexed += $result['count'];
                foreach ($seasons as $season) {
                    Cache::put('rag_index_season_' . $season->id, true, $this->cacheTime);
                }
            } else {
                $failedBatches++;
            }
        });

        return [
            'success' => $failedBatches === 0,
            'total_indexed' => $totalIndexed,
            'failed_batches' => $failedBatches
        ];
    }
}
