<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Fixture;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BettingService
{
    private FixturePredictService $fixturePredictService;
    private FixtureService $fixtureService;
    private BalanceService $balanceService;

    public function __construct(
        FixturePredictService $fixturePredictService,
        FixtureService $fixtureService,
        BalanceService $balanceService
    ) {
        $this->fixturePredictService = $fixturePredictService;
        $this->fixtureService = $fixtureService;
        $this->balanceService = $balanceService;
    }

    /**
     * Place a new bet
     */
    public function placeBet(User $user, int $fixtureId, string $betType, float $amount, ?array $predictedScore = null): array
    {
        try {
            DB::beginTransaction();

            // Check if fixture exists and is not finished
            $fixture = $this->fixtureService->getFixtureById($fixtureId);
            if (!$fixture || $fixture['fixture']->getStatus() == 'FINISHED') {
                return [
                    'success' => false,
                    'error' => 'Fixture not found or already finished'
                ];
            }

            // Get prediction for odds calculation
            $prediction = $this->fixturePredictService->predictMatchOutcome($fixtureId);
            if (!$prediction['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get match prediction'
                ];
            }

            // Calculate odds based on prediction
            $odds = $this->calculateOdds($betType, $prediction['prediction']);
            
            // Calculate potential win
            $potentialWin = $amount * $odds;

            // Create bet
            $bet = new Bet();
            $bet->user_id = $user->id;
            $bet->fixture_id = $fixtureId;
            $bet->bet_type = $betType;
            $bet->predicted_score = $predictedScore;
            $bet->amount = $amount;
            $bet->odds = $odds;
            $bet->potential_win = $potentialWin;
            $bet->status = 'PENDING';
            $bet->save();

            // Trừ tiền từ số dư người dùng
            $betDetails = [
                'fixture_id' => $fixtureId,
                'bet_type' => $betType,
                'amount' => $amount,
                'odds' => $odds,
                'potential_win' => $potentialWin
            ];
            
            $balanceResult = $this->balanceService->placeBet($user, $amount, $betDetails);
            if (!$balanceResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'error' => $balanceResult['error']
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'bet' => $bet,
                'new_balance' => $balanceResult['new_balance']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate odds based on prediction and bet type
     */
    private function calculateOdds(string $betType, array $prediction): float
    {
        $winProbabilities = $prediction['win_probability'];
        
        switch ($betType) {
            case 'WIN':
                return 1 / ($winProbabilities['home'] / 100);
            case 'DRAW':
                return 1 / ($winProbabilities['draw'] / 100);
            case 'LOSS':
                return 1 / ($winProbabilities['away'] / 100);
            case 'SCORE':
                // Higher odds for exact score prediction
                return 5.0;
            default:
                return 1.0;
        }
    }

    /**
     * Process bet results after match completion
     */
    public function processBetResults(int $fixtureId): array
    {
        try {
            $fixture = $this->fixtureService->getFixtureById($fixtureId);
            if (!$fixture || $fixture['fixture']->getStatus() != 'FINISHED') {
                return [
                    'success' => false,
                    'error' => 'Fixture not found or not finished'
                ];
            }

            $bets = Bet::where('fixture_id', $fixtureId)
                      ->where('status', 'PENDING')
                      ->get();

            $actualScore = [
                'home' => $fixture['fixture']->getScore()->getFullTime()['home'] ?? 0,
                'away' => $fixture['fixture']->getScore()->getFullTime()['away'] ?? 0
            ];

            foreach ($bets as $bet) {
                $result = $this->determineBetResult($bet, $actualScore);
                $bet->status = $result['status'];
                $bet->result = $result['result'];
                $bet->save();

                // Nếu thắng cược, cộng tiền thắng vào số dư
                if ($result['status'] === 'WON') {
                    $betDetails = [
                        'fixture_id' => $fixtureId,
                        'bet_type' => $bet->bet_type,
                        'amount' => $bet->amount,
                        'odds' => $bet->odds,
                        'potential_win' => $bet->potential_win
                    ];
                    $this->balanceService->processWin($bet->user, $bet->potential_win, $betDetails);
                }
            }

            return [
                'success' => true,
                'processed_bets' => count($bets)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Determine if a bet has won or lost
     */
    private function determineBetResult(Bet $bet, array $actualScore): array
    {
        $homeScore = $actualScore['home'];
        $awayScore = $actualScore['away'];

        switch ($bet->bet_type) {
            case 'WIN':
                $result = $homeScore > $awayScore ? 'WON' : 'LOST';
                break;
            case 'DRAW':
                $result = $homeScore == $awayScore ? 'WON' : 'LOST';
                break;
            case 'LOSS':
                $result = $homeScore < $awayScore ? 'WON' : 'LOST';
                break;
            case 'SCORE':
                $predictedScore = $bet->predicted_score;
                $result = ($predictedScore['home'] == $homeScore && 
                          $predictedScore['away'] == $awayScore) ? 'WON' : 'LOST';
                break;
            default:
                $result = 'LOST';
        }

        return [
            'status' => $result,
            'result' => "{$homeScore}-{$awayScore}"
        ];
    }

    /**
     * Get user's betting history
     */
    public function getUserBettingHistory(User $user, ?int $fixtureId = null): array
    {
        $bets = Bet::where('user_id', $user->id)
                   ->with('fixture')
                   ->when($fixtureId, function ($query) use ($fixtureId) {
                       return $query->where('fixture_id', $fixtureId);
                   })
                   ->orderBy('created_at', 'desc')
                   ->get();

        return [
            'success' => true,
            'bets' => $bets
        ];
    }
} 