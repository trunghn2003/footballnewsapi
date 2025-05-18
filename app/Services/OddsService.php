<?php

namespace App\Services;

class OddsService
{
    /**
     * Tính tỉ lệ cược dựa trên nhiều yếu tố
     */
    public function calculateOdds(array $prediction, array $fixtureData): array
    {
        // Lấy xác suất cơ bản từ dự đoán
        $baseProbabilities = $prediction['win_probability'];

        // Tính tỉ lệ cược cơ bản
        $baseOdds = [
            'home' => $this->probabilityToOdds($baseProbabilities['home']),
            'draw' => $this->probabilityToOdds($baseProbabilities['draw']),
            'away' => $this->probabilityToOdds($baseProbabilities['away'])
        ];

        // Điều chỉnh tỉ lệ cược dựa trên các yếu tố
        $adjustedOdds = $this->adjustOdds($baseOdds, $fixtureData);

        // Tính tỉ lệ cược cho các loại cược khác
        return [
            '1x2' => [
                'home' => $adjustedOdds['home'],
                'draw' => $adjustedOdds['draw'],
                'away' => $adjustedOdds['away']
            ],
            'handicap' => $this->calculateHandicapOdds($adjustedOdds, $fixtureData),
            'over_under' => $this->calculateOverUnderOdds($prediction, $fixtureData),
            'correct_score' => $this->calculateCorrectScoreOdds($prediction, $fixtureData),
            'both_teams_to_score' => $this->calculateBTTSOdds($prediction, $fixtureData)
        ];
    }

    /**
     * Chuyển đổi xác suất thành tỉ lệ cược
     */
    private function probabilityToOdds(float $probability): float
    {
        if ($probability <= 0) {
            return 999.0;
        }
        return round(100 / $probability, 2);
    }

    /**
     * Điều chỉnh tỉ lệ cược dựa trên các yếu tố
     */
    private function adjustOdds(array $baseOdds, array $fixtureData): array
    {
        $adjustedOdds = $baseOdds;

        // Điều chỉnh dựa trên sân nhà/khách
        if ($fixtureData['is_home_advantage']) {
            $adjustedOdds['home'] *= 0.95; // Giảm tỉ lệ cho đội nhà
            $adjustedOdds['away'] *= 1.05; // Tăng tỉ lệ cho đội khách
        }

        // Điều chỉnh dựa trên phong độ gần đây
        if ($fixtureData['home_team_form'] > $fixtureData['away_team_form']) {
            $adjustedOdds['home'] *= 0.9;
            $adjustedOdds['away'] *= 1.1;
        } elseif ($fixtureData['away_team_form'] > $fixtureData['home_team_form']) {
            $adjustedOdds['home'] *= 1.1;
            $adjustedOdds['away'] *= 0.9;
        }

        // Điều chỉnh dựa trên tỉ lệ đặt cược
        if (isset($fixtureData['betting_ratio'])) {
            $ratio = $fixtureData['betting_ratio'];
            if ($ratio > 1.5) { // Nhiều người đặt đội nhà
                $adjustedOdds['home'] *= 1.1;
                $adjustedOdds['away'] *= 0.9;
            } elseif ($ratio < 0.67) { // Nhiều người đặt đội khách
                $adjustedOdds['home'] *= 0.9;
                $adjustedOdds['away'] *= 1.1;
            }
        }

        return $adjustedOdds;
    }

    /**
     * Tính tỉ lệ cược kèo handicap
     */
    private function calculateHandicapOdds(array $baseOdds, array $fixtureData): array
    {
        $handicapOdds = [];

        // Tính handicap dựa trên sức mạnh tương đối
        $strengthDiff = $fixtureData['home_team_strength'] - $fixtureData['away_team_strength'];

        // Tạo các mức handicap
        $handicaps = [-2.5, -2, -1.5, -1, -0.5, 0, 0.5, 1, 1.5, 2, 2.5];

        foreach ($handicaps as $handicap) {
            $handicapOdds[$handicap] = [
                'home' => $this->calculateHandicapOddsForTeam($baseOdds['home'], $strengthDiff, $handicap),
                'away' => $this->calculateHandicapOddsForTeam($baseOdds['away'], $strengthDiff, -$handicap)
            ];
        }

        return $handicapOdds;
    }

    /**
     * Tính tỉ lệ cược cho một đội trong kèo handicap
     */
    private function calculateHandicapOddsForTeam(float $baseOdds, float $strengthDiff, float $handicap): float
    {
        $adjustment = ($strengthDiff - $handicap) * 0.1;
        return round($baseOdds * (1 + $adjustment), 2);
    }

    /**
     * Tính tỉ lệ cược tài/xỉu
     */
    private function calculateOverUnderOdds(array $prediction, array $fixtureData): array
    {
        $overUnderOdds = [];
        $expectedGoals = $prediction['predicted_score']['home'] + $prediction['predicted_score']['away'];

        // Tạo các mức tài/xỉu
        $lines = [0.5, 1.5, 2.5, 3.5, 4.5, 5.5];

        foreach ($lines as $line) {
            $overUnderOdds[$line] = [
                'over' => $this->calculateOverUnderOddsForLine($expectedGoals, $line, true),
                'under' => $this->calculateOverUnderOddsForLine($expectedGoals, $line, false)
            ];
        }

        return $overUnderOdds;
    }

    /**
     * Tính tỉ lệ cược cho một mức tài/xỉu
     */
    private function calculateOverUnderOddsForLine(float $expectedGoals, float $line, bool $isOver): float
    {
        $probability = $isOver ?
            $this->calculateOverProbability($expectedGoals, $line) :
            $this->calculateUnderProbability($expectedGoals, $line);

        return $this->probabilityToOdds($probability * 100);
    }

    /**
     * Tính xác suất tài
     */
    private function calculateOverProbability(float $expectedGoals, float $line): float
    {
        // Sử dụng phân phối Poisson để tính xác suất
        $probability = 0;
        for ($i = ceil($line); $i <= 10; $i++) {
            $probability += pow($expectedGoals, $i) * exp(-$expectedGoals) / factorial($i);
        }
        return $probability;
    }

    /**
     * Tính xác suất xỉu
     */
    private function calculateUnderProbability(float $expectedGoals, float $line): float
    {
        return 1 - $this->calculateOverProbability($expectedGoals, $line);
    }

    /**
     * Tính tỉ lệ cược tỉ số chính xác
     */
    private function calculateCorrectScoreOdds(array $prediction, array $fixtureData): array
    {
        $correctScoreOdds = [];
        $homeStrength = $fixtureData['home_team_strength'];
        $awayStrength = $fixtureData['away_team_strength'];

        // Tạo các tỉ số có khả năng cao
        for ($home = 0; $home <= 5; $home++) {
            for ($away = 0; $away <= 5; $away++) {
                $probability = $this->calculateScoreProbability($home, $away, $homeStrength, $awayStrength);
                if ($probability > 0.01) { // Chỉ lấy các tỉ số có xác suất > 1%
                    $correctScoreOdds["{$home}-{$away}"] = $this->probabilityToOdds($probability * 100);
                }
            }
        }

        return $correctScoreOdds;
    }

    /**
     * Tính xác suất của một tỉ số
     */
    private function calculateScoreProbability(int $homeGoals, int $awayGoals, float $homeStrength, float $awayStrength): float
    {
        $homeExpected = $homeStrength * 1.5;
        $awayExpected = $awayStrength * 1.2;

        $homeProb = pow($homeExpected, $homeGoals) * exp(-$homeExpected) / factorial($homeGoals);
        $awayProb = pow($awayExpected, $awayGoals) * exp(-$awayExpected) / factorial($awayGoals);

        return $homeProb * $awayProb;
    }

    /**
     * Tính tỉ lệ cược cả hai đội ghi bàn
     */
    private function calculateBTTSOdds(array $prediction, array $fixtureData): array
    {
        $homeScoringProb = $this->calculateTeamScoringProbability($fixtureData['home_team_strength']);
        $awayScoringProb = $this->calculateTeamScoringProbability($fixtureData['away_team_strength']);

        $bttsProb = $homeScoringProb * $awayScoringProb;
        $noBttsProb = 1 - $bttsProb;

        return [
            'yes' => $this->probabilityToOdds($bttsProb * 100),
            'no' => $this->probabilityToOdds($noBttsProb * 100)
        ];
    }

    /**
     * Tính xác suất một đội ghi bàn
     */
    private function calculateTeamScoringProbability(float $teamStrength): float
    {
        return 1 - exp(-$teamStrength * 1.2);
    }
}

// Hàm tính giai thừa
function factorial($n)
{
    if ($n <= 1) {
        return 1;
    }
    return $n * factorial($n - 1);
}
