<?php

namespace App\Services;

use App\Repositories\NewsRepository;
use App\Models\Team;
use App\Models\User;
use App\Repositories\TeamRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\PushNotification;
use Stichoza\GoogleTranslate\GoogleTranslate;

class NewsService
{
    use PushNotification;

    protected $newsRepository;
    protected $teamRepository;
    protected $apiKey;
    protected $baseUrl = 'http://3.1.100.34:5000/api/scrape-articles';

    public function __construct(NewsRepository $newsRepository, TeamRepository $teamRepository)
    {
        $this->newsRepository = $newsRepository;
        $this->teamRepository = $teamRepository;
    }

    public function fetchNewsFromApi($competitionId)
    {
        $response = Http::get($this->baseUrl . '/' . $competitionId);
        // dd($response);
        try {
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch news: ' . $response->body());
            }

            $data = $response->json();
            return $data['results'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching news: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rawNewsFromApi()
    {
        set_time_limit(3000000);
        $ids = [2001, 2002, 2014, 2015, 2021, 2019];
        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                $articles = $this->fetchNewsFromApi($id);
                $this->storeNewsFromApi($articles, $id);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in rawNewsFromApi: ' . $e->getMessage());
            throw $e;
        }

        // dd($response);
    }
    public function storeNewsFromApi(array $newsArticles, $competitionId)
    {
        DB::beginTransaction();
        try {
            $translator = new GoogleTranslate();
            $translator->setSource('en')->setTarget('vi');

            foreach ($newsArticles as $article) {
                // Dịch tiêu đề và nội dung sang tiếng Việt
                $article['title'] = $translator->translate($article['title'] ?? '');
                $article['content'] = $translator->translate($article['content'] ?? '');


                $article['competition_id'] = $competitionId;

                // Lưu bài viết vào cơ sở dữ liệu
                $news = $this->newsRepository->create($article);

                // Check for team names in article content
                $this->processTeamRelationships($news, $article);

                // Send notifications to users interested in this competition
                $this->notifyInterestedUsers($news, $competitionId);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            Log::error('Error in NewsService: ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }

    protected function processTeamRelationships($news, $article)
    {
        if (!$news) {
            return; // Skip if news article was not created (duplicate title)
        }

        $content = strtolower($article['title'] . ' ' . $article['description'] . ' ' . $article['content']);

        $teams = $this->teamRepository->findAll();

        foreach ($teams as $team) {
            $teamName = strtolower($team->name);
            $teamShortname = strtolower($team->short_name);
            if (strpos($content, $teamName) !== false || strpos($content, $teamShortname) !== false) {
                $news->teams()->attach($team->id);
            }
        }
    }

    protected function notifyInterestedUsers($news, $competitionId)
    {
        // Get users who should be notified about this news
        $users = User::whereNotNull('fcm_token')->get();

        foreach ($users as $user) {
            $prefs = json_decode($user->notification_pref, true);
            if (!$prefs) continue;

            // Lấy danh sách team_ids từ tin tức
            $newsTeamIds = $news->teams()->pluck('id')->toArray();

            // Thiết lập giá trị mặc định
            $shouldNotify = false;
            $notificationType = '';
            $title = '';

            // Kiểm tra cài đặt thông báo theo định dạng mới
            // 1. Kiểm tra cài đặt toàn cục trước
            $globalSettings = $prefs['global_settings'] ?? [];

            // 2. Kiểm tra tin tức giải đấu
            if (isset($globalSettings['competition_news']) && $globalSettings['competition_news']) {
                // Kiểm tra cài đặt riêng cho giải đấu cụ thể
                $competitionSettings = $prefs['competition_settings'] ?? [];
                $hasSpecificSetting = false;

                // Nếu có cài đặt cụ thể cho giải đấu này
                if (is_array($competitionSettings) && isset($competitionSettings[$competitionId])) {
                    $hasSpecificSetting = true;
                    if ($competitionSettings[$competitionId]['competition_news'] ?? false) {
                        $notificationType = 'competition_news';
                        $title = 'Tin tức giải đấu mới';
                        $shouldNotify = true;
                    }
                }

                // Nếu không có cài đặt cụ thể, sử dụng cài đặt toàn cục
                if (!$hasSpecificSetting) {
                    $notificationType = 'competition_news';
                    $title = 'Tin tức giải đấu mới';
                    $shouldNotify = true;
                }
            }

            // 3. Kiểm tra tin tức đội bóng
            if (!$shouldNotify && isset($globalSettings['team_news']) && $globalSettings['team_news'] && !empty($newsTeamIds)) {
                $teamSettings = $prefs['team_settings'] ?? [];
                $hasSpecificTeamSetting = false;

                // Kiểm tra xem có đội bóng nào trong tin tức mà người dùng có cài đặt riêng không
                foreach ($newsTeamIds as $teamId) {
                    if (is_array($teamSettings) && isset($teamSettings[$teamId])) {
                        $hasSpecificTeamSetting = true;
                        // Cài đặt thông báo chi tiết của một đội bóng gồm 3 loại:
                        // 1. team_news - Thông báo tin tức về đội bóng
                        // 2. match_reminders - Thông báo nhắc nhở trận đấu
                        // 3. match_score - Thông báo kết quả trận đấu

                        // Vì đây là tin tức nên chỉ cần kiểm tra team_news
                        if ($teamSettings[$teamId]['team_news'] ?? false) {
                            $notificationType = 'team_news';
                            $title = 'Tin tức đội bóng mới';
                            $shouldNotify = true;
                            break;
                        }
                    }
                }

                // Nếu không có cài đặt cụ thể cho đội bóng nào, sử dụng cài đặt toàn cục
                if (!$hasSpecificTeamSetting) {
                    $notificationType = 'team_news';
                    $title = 'Tin tức đội bóng mới';
                    $shouldNotify = true;
                }
            }

            try {
                $logo = $news->teams()->first() ? $news->teams()->first()->crest : $news->competition->emblem;
            } catch (\Exception $e) {
                Log::error('Error fetching team logo: ' . $e->getMessage());
                $logo = null;
            }

            if ($shouldNotify) {
                // Thêm thông tin team_ids và competition_id để kiểm tra cài đặt chi tiết
                $this->sendNotification(
                    $user->fcm_token,
                    $title,
                    $news->title,
                    [
                        'type' => $notificationType,
                        'news_id' => $news->id,
                        'screen' => "NewsView/?id=" . $news->id,
                        'user_id' => $user->id,
                        'logo' => $logo,
                        'team_ids' => $newsTeamIds,
                        'competition_id' => $competitionId
                    ]
                );
            }
        }
    }

    public function getNewsById($id)
    {
        try {
            $news = $this->newsRepository->getNewsById($id);
            $currentUserId = auth()->id();

            $comments = $news->comments()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($comment) use ($currentUserId) {
                    $comment->is_owner = $comment->user_id === $currentUserId;
                    return $comment;
                });
            $news = [
                'id' => $news->id,
                'title' => $news->title,
                'source' => $news->source,
                'content' => $news->content,
                'published_at' => $news->published_at,
                'competition_id' => $news->competition_id,
                'thumbnail' => $news->thumbnail,
                'comments_count' => count($comments),
                'is_saved' =>  $news->savedByUsers()->where('user_id', auth()->id())->exists() ?? false,
            ];

            return [
                'news' => $news,
                'comments' => $comments
            ];
        } catch (\Exception $e) {
            Log::error('Error in NewsService getNewsById: ' . $e->getMessage());
            throw $e;
        }
    }
    public function saveNews($newsId, $userId)
    {
        try {
            $news = $this->newsRepository->getNewsById($newsId);
            $user = User::findOrFail($userId);


            if (!$user->savedNews()->where('news_id', $newsId)->exists()) {
                $user->savedNews()->attach($newsId);
                return ['message' => 'Đã lưu bài viết thành công'];
            }

            return ['message' => 'Bài viết đã được lưu trước đó'];
        } catch (\Exception $e) {
            Log::error('Error saving news: ' . $e->getMessage());
            throw $e;
        }
    }

    public function unsaveNews($newsId, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->savedNews()->detach($newsId);
            return ['message' => 'Đã bỏ lưu bài viết'];
        } catch (\Exception $e) {
            Log::error('Error unsaving news: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getSavedNews($userId, $perPage = 10, $page = 1)
    {
        $user = User::findOrFail($userId);

        $result = $user->savedNews()
            ->orderBy('saved_news.created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($result->isEmpty()) {
            return [
                'news' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page'     => 0,
                    'total'        => 0
                ]
            ];
        }
        $paginationInfo = [
            'current_page' => $result->currentPage(),
            'per_page'     => $result->perPage(),
            'total'        => $result->total()
        ];

        $newsItems = $result->items();
        $mappedNews = array_map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'source' => $news->source,
                'content' => $news->content,
                'published_at' => $news->published_at,
                'competition_id' => $news->competition_id,
                'thumbnail' => $news->thumbnail,
                'comments' => $news->comments_count ?? count($news->comments),
                'is_saved' =>  $news->savedByUsers()->where('user_id', auth()->id())->exists() ?? false,
            ];
        }, $newsItems);

        return [
            'news' => $mappedNews,
            'pagination' => $paginationInfo
        ];
    }


    public function getLatestNews($perPage = 10, $page = 1, $filters = [])
    {
        $result = $this->newsRepository->getLatestNews($perPage, $page, $filters);


        $paginationInfo = [
            'current_page' => $result->currentPage(),
            'per_page'     => $result->perPage(),
            'total'        => $result->total()
        ];

        $newsItems = $result->items();
        $mappedNews = array_map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'source' => $news->source,
                'content' => $news->content,
                'published_at' => $news->published_at,
                'competition_id' => $news->competition_id,
                'thumbnail' => $news->thumbnail,
                'comments' => $news->comments_count ?? count($news->comments),
                'is_saved' =>  $news->savedByUsers()->where('user_id', auth()->id())->exists() ?? false,
            ];
        }, $newsItems);

        return [
            'news' => $mappedNews,
            'pagination' => $paginationInfo
        ];
    }
}
