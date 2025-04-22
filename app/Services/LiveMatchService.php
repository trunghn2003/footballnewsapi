<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveMatchService
{
    protected $baseUrl = 'https://br.vebo.xyz/api/match/live';

    public function getLiveMatches()
    {
        try {
            $response = Http::get($this->baseUrl);
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch live matches');
            }

            $data = $response->json();

            // Process matches by status
            $matches = collect($data['data'] ?? []);

            $processedData = [
                'live' => $matches->filter(function ($match) {
                    return $match['match_status'] === 'live';
                })->map(function ($match) {
                    return $this->formatMatch($match);
                })->values(),

                'finished' => $matches->filter(function ($match) {
                    return $match['match_status'] === 'finished';
                })->map(function ($match) {
                    return $this->formatMatch($match);
                })->values(),

                'upcoming' => $matches->filter(function ($match) {
                    return $match['match_status'] === 'pending';
                })->map(function ($match) {
                    return $this->formatMatch($match);
                })->values(),

                'counts' => [
                    'live' => $data['live_count']['football'] ?? 0,
                    'upcoming' => $data['live_count']['upcoming'] ?? 0
                ]
            ];

            return [
                'success' => true,
                'message' => null,
                'data' => $processedData
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching live matches: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch live matches',
                'data' => null
            ];
        }
    }

    protected function formatMatch($match)
    {
        return [
            'id' => $match['id'],
            'key_sync' => $match['key_sync'],
            'match_time' => [
                'timestamp' => $match['timestamp'],
                'date' => Carbon::createFromTimestamp($match['timestamp']/1000)->format('Y-m-d'),
                'time' => Carbon::createFromTimestamp($match['timestamp']/1000)->format('H:i'),
                'status' => $match['match_status'],
                'current_time' => $match['time_str'] ?? null
            ],
            'teams' => [
                'home' => [
                    'id' => $match['home']['id'],
                    'name' => $match['home']['name'],
                    'short_name' => $match['home']['short_name'],
                    'logo' => $match['home']['logo'],
                    'red_cards' => $match['home_red_cards']
                ],
                'away' => [
                    'id' => $match['away']['id'],
                    'name' => $match['away']['name'],
                    'short_name' => $match['away']['short_name'],
                    'logo' => $match['away']['logo'],
                    'red_cards' => $match['away_red_cards']
                ]
            ],
            'score' => [
                'home' => $match['scores']['home'],
                'away' => $match['scores']['away'],
                'winner' => $this->getWinnerText($match['win_code'])
            ],
            'tournament' => [
                'id' => $match['tournament']['unique_tournament']['id'],
                'name' => $match['tournament']['name'],
                'logo' => $match['tournament']['logo'],
                'priority' => $match['tournament']['priority']
            ],
            'match_details' => [
                'has_lineup' => $match['has_lineup'],
                'has_tracker' => $match['has_tracker'],
                'is_featured' => $match['is_featured'],
                'live_tracker_url' => $match['live_tracker'] ?? null,
                'commentators' => $match['commentators'] ?? []
            ]
        ];
    }

    protected function getWinnerText($winCode)
    {
        switch ($winCode) {
            case 1:
                return 'home';
            case 2:
                return 'away';
            case 3:
                return 'draw';
            default:
                return null;
        }
    }
}
