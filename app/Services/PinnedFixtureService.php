<?php

namespace App\Services;

use App\Repositories\PinnedFixtureRepository;
use App\Repositories\FixtureRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use LogicException;

class PinnedFixtureService
{
    protected $pinnedFixtureRepository;
    protected $fixtureRepository;

    public function __construct(
        PinnedFixtureRepository $pinnedFixtureRepository,
        FixtureRepository $fixtureRepository
    ) {
        $this->pinnedFixtureRepository = $pinnedFixtureRepository;
        $this->fixtureRepository = $fixtureRepository;
    }

    /**
     * Ghim một trận đấu
     *
     * @param int $fixtureId
     * @param int $userId
     * @param array $options
     * @return array
     */
    public function pinFixture(int $fixtureId, int $userId, array $options = [])
    {
        try {
            // Kiểm tra trận đấu có tồn tại không
            $fixture = $this->fixtureRepository->findById($fixtureId);
            if (!$fixture) {
                throw new LogicException('Trận đấu không tồn tại');
            }

            // Ghim trận đấu
            $pinnedFixture = $this->pinnedFixtureRepository->pinFixture($userId, $fixtureId, $options);

            return [
                'success' => true,
                'message' => 'Đã ghim trận đấu thành công',
                'pinned_fixture' => $pinnedFixture
            ];
        } catch (Exception $e) {
            Log::error('Ghim trận đấu thất bại: ' . $e->getMessage());
            throw new LogicException('Không thể ghim trận đấu: ' . $e->getMessage());
        }
    }

    /**
     * Bỏ ghim trận đấu
     *
     * @param int $fixtureId
     * @param int $userId
     * @return array
     */
    public function unpinFixture(int $fixtureId, int $userId)
    {
        try {
            // Kiểm tra trận đấu đã được ghim chưa
            if (!$this->pinnedFixtureRepository->isPinned($userId, $fixtureId)) {
                throw new LogicException('Trận đấu chưa được ghim');
            }

            // Bỏ ghim trận đấu
            $this->pinnedFixtureRepository->unpinFixture($userId, $fixtureId);

            return [
                'success' => true,
                'message' => 'Đã bỏ ghim trận đấu thành công'
            ];
        } catch (Exception $e) {
            Log::error('Bỏ ghim trận đấu thất bại: ' . $e->getMessage());
            throw new LogicException('Không thể bỏ ghim trận đấu: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách trận đấu đã ghim của người dùng
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function getUserPinnedFixtures(int $userId, int $perPage = 15, int $page = 1)
    {
        try {
            $result = $this->pinnedFixtureRepository->getUserPinnedFixtures($userId, $perPage, $page);

            $fixtures = $result->map(function ($pinnedFixture) {
                $fixture = $pinnedFixture->fixture;
                $homeTeam = $fixture->homeTeam;
                $awayTeam = $fixture->awayTeam;

                return [
                    'id' => $fixture->id,
                    'date' => $fixture->utc_date,
                    'status' => $fixture->status,
                    'matchday' => $fixture->matchday,
                    'stage' => $fixture->stage,
                    'home_team' => [
                        'id' => $homeTeam->id,
                        'name' => $homeTeam->name,
                        'short_name' => $homeTeam->short_name,
                        'tla' => $homeTeam->tla,
                        'crest' => $homeTeam->crest,
                    ],
                    'away_team' => [
                        'id' => $awayTeam->id,
                        'name' => $awayTeam->name,
                        'short_name' => $awayTeam->short_name,
                        'tla' => $awayTeam->tla,
                        'crest' => $awayTeam->crest,
                    ],
                    'score' => [
                        'home' => $fixture->full_time_home_score,
                        'away' => $fixture->full_time_away_score,
                    ],
                    'competition' => $fixture->competition ? [
                        'id' => $fixture->competition->id,
                        'name' => $fixture->competition->name,
                        'emblem' => $fixture->competition->emblem,
                    ] : null,
                    'venue' => $fixture->venue,
                    'notify_options' => [
                        'notify_before' => $pinnedFixture->notify_before,
                        'notify_result' => $pinnedFixture->notify_result,
                    ]
                ];
            });

            return [
                'fixtures' => $fixtures,
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ]
            ];
        } catch (Exception $e) {
            Log::error('Lấy danh sách trận đấu đã ghim thất bại: ' . $e->getMessage());
            throw new LogicException('Không thể lấy danh sách trận đấu đã ghim: ' . $e->getMessage());
        }
    }

    /**
     * Kiểm tra trạng thái ghim trận đấu
     *
     * @param int $fixtureId
     * @param int $userId
     * @return array
     */
    public function checkPinStatus(int $fixtureId, int $userId)
    {
        try {
            $isPinned = $this->pinnedFixtureRepository->isPinned($userId, $fixtureId);

            return [
                'success' => true,
                'is_pinned' => $isPinned
            ];
        } catch (Exception $e) {
            Log::error('Kiểm tra trạng thái ghim thất bại: ' . $e->getMessage());
            throw new LogicException('Không thể kiểm tra trạng thái ghim: ' . $e->getMessage());
        }
    }
}
