<?php

namespace App\Repositories;

use App\Models\PinnedFixture;

class PinnedFixtureRepository
{
    protected $model;

    public function __construct(PinnedFixture $model)
    {
        $this->model = $model;
    }

    /**
     * Ghim một trận đấu cho người dùng
     *
     * @param int $userId
     * @param int $fixtureId
     * @param array $options
     * @return PinnedFixture
     */
    public function pinFixture(int $userId, int $fixtureId, array $options = [])
    {
        return $this->model->updateOrCreate(
            ['user_id' => $userId, 'fixture_id' => $fixtureId],
            array_merge([
                'notify_before' => true,
                'notify_result' => true,
            ], $options)
        );
    }

    /**
     * Bỏ ghim một trận đấu
     *
     * @param int $userId
     * @param int $fixtureId
     * @return int
     */
    public function unpinFixture(int $userId, int $fixtureId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('fixture_id', $fixtureId)
            ->delete();
    }

    /**
     * Lấy danh sách các trận đấu đã ghim của người dùng
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserPinnedFixtures(int $userId, int $perPage = 15, int $page = 1)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['fixture', 'fixture.homeTeam', 'fixture.awayTeam', 'fixture.competition'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Kiểm tra người dùng đã ghim trận đấu chưa
     *
     * @param int $userId
     * @param int $fixtureId
     * @return bool
     */
    public function isPinned(int $userId, int $fixtureId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('fixture_id', $fixtureId)
            ->exists();
    }

    /**
     * Lấy danh sách users đã ghim một trận đấu
     *
     * @param int $fixtureId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersForFixture(int $fixtureId)
    {
        return $this->model
            ->where('fixture_id', $fixtureId)
            ->with('user')
            ->get();
    }
}
