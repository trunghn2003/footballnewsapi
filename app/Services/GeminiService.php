<?php

namespace App\Services;

use Gemini\Data\GenerationConfig;
use Gemini\Enums\HarmBlockThreshold;
use Gemini\Data\SafetySetting;
use Gemini\Enums\HarmCategory;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;
use Gemini\Enums\ModelType;

class GeminiService
{
    /**
     * Generate content using Gemini AI
     *
     * @param string $prompt The prompt to send to Gemini
     * @return array Response containing the generated content or error
     */
    public function generateContent(string $prompt): array
    {
        try {
            // Configure safety settings
            $safetySettingDangerousContent = new SafetySetting(
                category: HarmCategory::HARM_CATEGORY_DANGEROUS_CONTENT,
                threshold: HarmBlockThreshold::BLOCK_ONLY_HIGH
            );

            $safetySettingHateSpeech = new SafetySetting(
                category: HarmCategory::HARM_CATEGORY_HATE_SPEECH,
                threshold: HarmBlockThreshold::BLOCK_ONLY_HIGH
            );

            // Configure generation parameters
            $generationConfig = new GenerationConfig(
                maxOutputTokens: 1000,
                temperature: 0.7,
                topP: 0.8,
                topK: 40
            );


            $response = Gemini::generativeModel(ModelType::GEMINI_FLASH)
                // ->withModel('models/gemini-2.0-flash-lite')
                ->withSafetySetting($safetySettingDangerousContent)
                ->withSafetySetting($safetySettingHateSpeech)
                ->withGenerationConfig($generationConfig)
                ->generateContent($prompt);


            $text = $response->text();

            // Extract win probabilities
            preg_match('/Home Win: (\d+)%/', $text, $homeWinMatches);
            preg_match('/Draw: (\d+)%/', $text, $drawMatches);
            preg_match('/Away Win: (\d+)%/', $text, $awayWinMatches);

            // Extract predicted score
            preg_match('/Predicted score: (\d+)-(\d+)/', $text, $scoreMatches);

            // Extract key factors
            preg_match('/Key factors influencing the prediction:(.*?)Confidence level:/s', $text, $factorsMatches);
            $factorsText = $factorsMatches[1] ?? '';
            $factors = [];
            preg_match_all('/- (.*?)(?:\n|$)/', $factorsText, $factorMatches);
            if (!empty($factorMatches[1])) {
                $factors = $factorMatches[1];
            }

            // Extract confidence level
            preg_match('/Confidence level: (\d+)%/', $text, $confidenceMatches);

            return [
                'success' => true,
                'win_probability' => [
                    'home' => (int)($homeWinMatches[1] ?? 0),
                    'draw' => (int)($drawMatches[1] ?? 0),
                    'away' => (int)($awayWinMatches[1] ?? 0)
                ],
                'predicted_score' => [
                    'home' => (int)($scoreMatches[1] ?? 0),
                    'away' => (int)($scoreMatches[2] ?? 0)
                ],
                'key_factors' => $factors,
                'confidence_level' => (int)($confidenceMatches[1] ?? 0),
                'raw_response' => $text
            ];
        } catch (\Exception $e) {
            Log::error("Gemini API error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
