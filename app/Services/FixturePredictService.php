<?php

namespace App\Services;

use App\Models\FixturePrediction;
use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FixturePredictService
{
    private FixtureService $fixtureService;
    private GeminiService $geminiService;

    public function __construct(FixtureService $fixtureService, GeminiService $geminiService)
    {
        $this->fixtureService = $fixtureService;
        $this->geminiService = $geminiService;
    }

    /**
     * Predict match outcome based on historical data
     *
     * @param int $fixtureId The ID of the upcoming fixture to predict
     * @return array Prediction results including win probability and analysis
     */
    public function predictMatchOutcome(int $fixtureId): array
    {
        $fixture = $this->fixtureService->getFixtureById($fixtureId);
        if (!$fixture || $fixture['fixture']->getStatus() == 'FINISHED') {
            return [
                'success' => false,
                'error' => 'Fixture already finished'
            ];
        }

        try {
            $existingPrediction = FixturePrediction::where('fixture_id', $fixtureId)->first();
            if ($existingPrediction) {
                return [
                    'success' => true,
                    'prediction' => [
                        'success' => true,
                        'win_probability' => $existingPrediction->win_probability,
                        'predicted_score' => $existingPrediction->predicted_score,
                        'key_factors' => $existingPrediction->key_factors,
                        'confidence_level' => $existingPrediction->confidence_level,
                        'raw_response' => $existingPrediction->raw_response
                    ],
                    'analysis_data' => $existingPrediction->analysis_data
                ];
            }
            // Get head-to-head data
            $headToHeadData = $this->fixtureService->getHeadToHeadFixturesByFixtureId($fixtureId);

            // Get current fixture details
            $currentFixture = $this->fixtureService->getFixtureById($fixtureId);
            if (!$currentFixture || !isset($currentFixture['fixture'])) {
                return [
                    'success' => false,
                    'error' => 'Fixture not found'
                ];
            }

            $fixture = $currentFixture['fixture'];

            // Get recent fixtures for both teams
            $homeTeamRecentFixtures = $this->fixtureService->getRecentFixturesByTeam($fixture->getHomeTeam()->getId());
            $awayTeamRecentFixtures = $this->fixtureService->getRecentFixturesByTeam($fixture->getAwayTeam()->getId());

            // Prepare data for Gemini analysis
            $analysisData = [
                'head_to_head' => $headToHeadData['stats'],
                'home_team_recent' => $this->formatRecentFixtures($homeTeamRecentFixtures),
                'away_team_recent' => $this->formatRecentFixtures($awayTeamRecentFixtures),
                'upcoming_match' => [
                    'home_team' => $fixture->getHomeTeam()->getName(),
                    'away_team' => $fixture->getAwayTeam()->getName(),
                    'competition' => $fixture->getCompetition()->getName(),
                    'date' => $fixture->getUtcDate()
                ]
            ];

            // Create prompt for Gemini
            $prompt = $this->createAnalysisPrompt($analysisData);

            // Get prediction from Gemini
            $prediction = $this->geminiService->generateContent($prompt);

            if (!$prediction['success']) {
                return [
                    'success' => false,
                    'error' => $prediction['error'] ?? 'Failed to generate prediction'
                ];
            }
             $translator = new GoogleTranslate();
            $translator->setSource('en')->setTarget('vi');

            // Create and save the prediction
            $fixturePrediction = new FixturePrediction();
            $fixturePrediction->fixture_id = $fixtureId;
            $fixturePrediction->win_probability = $prediction['win_probability'];
            $fixturePrediction->predicted_score = ($prediction['predicted_score']);
            $string = "";
            foreach ($prediction['key_factors'] as $key => $value) {
                $string .= $value . "\n";
            }
            // dd($translator->translate($string));
            $string = $translator->translate($string);
            $fixturePrediction->key_factors =  $string;
            $fixturePrediction->confidence_level = $prediction['confidence_level'];
            $fixturePrediction->raw_response = $prediction['raw_response'];
            $fixturePrediction->analysis_data = $analysisData;
            $fixturePrediction->save();

            return [
                'success' => true,
                'prediction' => [
                    'success' => true,
                    'win_probability' => $prediction['win_probability'],
                    'predicted_score' => $prediction['predicted_score'],
                    'key_factors' => $string,
                    'confidence_level' => $prediction['confidence_level'],
                    'raw_response' => $prediction['raw_response']
                ],
                'analysis_data' => $analysisData
            ];
        } catch (\Exception $e) {
            Log::error("Match prediction failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format recent fixtures data for analysis
     */
    private function formatRecentFixtures(array $fixtures): array
    {
        $formatted = [];
        foreach ($fixtures['fixtures'] as $fixture) {
            $formatted[] = [
                'date' => $fixture->getUtcDate(),
                'home_team' => $fixture->getHomeTeam()->getName(),
                'away_team' => $fixture->getAwayTeam()->getName(),
                'score' => [
                    'home' => $fixture->getScore()->getFullTime()['home'] ?? 0,
                    'away' => $fixture->getScore()->getFullTime()['away'] ?? 0
                ],
                'competition' => $fixture->getCompetition()->getName()
            ];
        }
        return $formatted;
    }

    /**
     * Create a detailed prompt for Gemini analysis
     */
    private function createAnalysisPrompt(array $data): string
    {
        $prompt = "You are a football match prediction expert. Analyze this upcoming match and provide a detailed prediction based on the following data:\n\n";

        // Add match context
        $prompt .= "Upcoming Match:\n";
        $prompt .= "{$data['upcoming_match']['home_team']} vs {$data['upcoming_match']['away_team']}\n";
        $prompt .= "Competition: {$data['upcoming_match']['competition']}\n";
        $prompt .= "Date: {$data['upcoming_match']['date']}\n\n";

        // Add head-to-head stats
        $prompt .= "Head-to-Head Statistics:\n";
        $prompt .= "Total matches played: {$data['head_to_head']['team1']['total_matches']}\n";
        $prompt .= "{$data['upcoming_match']['home_team']} wins: {$data['head_to_head']['team1']['wins']}\n";
        $prompt .= "{$data['upcoming_match']['away_team']} wins: {$data['head_to_head']['team2']['wins']}\n";
        $prompt .= "Draws: {$data['head_to_head']['team1']['draws']}\n\n";

        // Add recent form for home team
        $prompt .= "Recent form for {$data['upcoming_match']['home_team']}:\n";
        foreach ($data['home_team_recent'] as $match) {
            $prompt .= "{$match['date']}: {$match['home_team']} {$match['score']['home']} - {$match['score']['away']} {$match['away_team']}\n";
        }
        $prompt .= "\n";

        // Add recent form for away team
        $prompt .= "Recent form for {$data['upcoming_match']['away_team']}:\n";
        foreach ($data['away_team_recent'] as $match) {
            $prompt .= "{$match['date']}: {$match['home_team']} {$match['score']['home']} - {$match['score']['away']} {$match['away_team']}\n";
        }
        $prompt .= "\n";

        // Add prediction request with specific format
        $prompt .= "Based on this data, please provide a detailed match prediction in the following format:\n\n";
        $prompt .= "Win probability:\n";
        $prompt .= "Home Win: [percentage]%\n";
        $prompt .= "Draw: [percentage]%\n";
        $prompt .= "Away Win: [percentage]%\n\n";
        $prompt .= "Predicted score: [home_score]-[away_score]\n\n";
        $prompt .= "Key factors influencing the prediction:\n";
        $prompt .= "- [Factor 1]\n";
        $prompt .= "- [Factor 2]\n";
        $prompt .= "- [Factor 3]\n\n";
        $prompt .= "Confidence level: [percentage]%\n\n";
        $prompt .= "Please ensure your response follows this exact format for accurate parsing.";

        return $prompt;
    }
}
