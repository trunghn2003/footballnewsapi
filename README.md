# Football News API Documentation

## Base URL
```
https://api.footballnews.com/api
```

## Authentication
Tất cả các API yêu cầu xác thực đều sử dụng JWT (JSON Web Token). Token cần được gửi trong header:
```http
Authorization: Bearer <your_jwt_token>
```

## API Endpoints

### 🔐 Authentication
```http
POST   /api/register                # Đăng ký tài khoản mới
POST   /api/login                   # Đăng nhập
POST   /api/logout                  # Đăng xuất (JWT required)
```

### 👤 User Profile
```http
GET    /api/profile                 # Lấy thông tin profile
POST   /api/profile/update          # Cập nhật thông tin profile
DELETE /api/profile/avatar          # Xóa avatar
```

### ⚽ Teams
```http
GET    /api/teams                   # Lấy danh sách đội bóng
GET    /api/teams/{teamId}          # Chi tiết đội bóng
POST   /api/teams/favorite/{teamId}  # Thêm đội bóng yêu thích
DELETE /api/teams/teams/{teamId}     # Xóa đội bóng yêu thích
GET    /api/teams/favorite          # Lấy danh sách đội bóng yêu thích
```

### 🏆 Competitions
```http
GET    /api/competitions            # Lấy tất cả giải đấu
GET    /api/competitions/{id}       # Chi tiết giải đấu
GET    /api/featured/competitions   # Giải đấu nổi bật
GET    /api/favorite/competitions   # Giải đấu yêu thích
POST   /api/competitions/favorite/{id} # Thêm giải đấu yêu thích
DELETE /api/competitions/favorite/{id} # Xóa giải đấu yêu thích
```

### 📰 News
```http
GET    /api/news                    # Lấy tất cả tin tức
GET    /api/news/{newsId}          # Chi tiết tin tức
POST   /api/news/{id}/save         # Lưu tin tức
DELETE /api/news/{id}/save         # Bỏ lưu tin tức
GET    /api/news/saved/get         # Lấy tin tức đã lưu
GET    /api/scrape-articles/{competitionId} # Lấy tin tức theo giải đấu
```

### 💬 Comments
```http
GET    /api/news/{newsId}/comments  # Lấy comments của tin tức
POST   /api/comments               # Tạo comment mới
PUT    /api/comments/{commentId}   # Cập nhật comment
DELETE /api/comments/{commentId}   # Xóa comment
GET    /api/comments/{commentId}   # Chi tiết comment
```

### ⚔️ Fixtures (Trận đấu)
```http
GET    /api/fixtures               # Danh sách trận đấu
GET    /api/fixtures/{id}          # Chi tiết trận đấu
GET    /api/fixtures/competition/season # Trận đấu theo mùa giải
GET    /api/fixtures/byRound/{competitionId} # Trận đấu theo vòng đấu
GET    /api/fixtures/team/{teamId}/recent    # Trận đấu gần đây của đội
GET    /api/fixtures/team/{teamId}/upcoming  # Trận đấu sắp tới của đội
GET    /api/fixtures/head-to-head/{fixtureId} # Đối đầu
GET    /api/fixtures/predict/{fixtureId}     # Dự đoán trận đấu
GET    /api/fixtures/lineup/{fixtureId}      # Đội hình trận đấu
GET    /api/matches/live           # Trận đấu đang diễn ra
```

### 📊 Standings (Bảng xếp hạng)
```http
GET    /api/standings              # Lấy bảng xếp hạng
GET    /api/standings/matchday     # BXH theo vòng đấu
GET    /api/standings/type         # BXH theo loại
GET    /api/competitions/{id}/standings    # BXH của giải đấu
GET    /api/competitions/{id}/standings/{type} # BXH theo loại của giải đấu
```

### 💰 Betting System
```http
POST   /api/betting/place-bet      # Đặt cược
GET    /api/betting/history        # Lịch sử cược
POST   /api/betting/process-results/{fixtureId} # Xử lý kết quả cược
GET    /api/betting/rankings       # Bảng xếp hạng người chơi
```

### 💳 Balance Management
```http
POST   /api/balance/deposit        # Nạp tiền
POST   /api/balance/withdraw       # Rút tiền
GET    /api/balance               # Xem số dư
GET    /api/balance/transactions  # Lịch sử giao dịch
```

### 🔔 Notifications
```http
GET    /api/notifications         # Lấy thông báo
POST   /api/notifications/markAsRead/{id} # Đánh dấu đã đọc
GET    /api/notifications/preferences     # Lấy cài đặt thông báo
POST   /api/notifications/preferences     # Cập nhật cài đặt thông báo
```

### 🔍 Search
```http
GET    /api/search                # Tìm kiếm tổng hợp
```

### 🌍 Areas
```http
GET    /api/areas                # Danh sách khu vực
GET    /api/areas/{id}           # Chi tiết khu vực
```

## Request/Response Examples

### Headers

#### Required Headers
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>        # Cho các API yêu cầu xác thực
```

#### Optional Headers
```http
Accept-Language: vi                  # Ngôn ngữ phản hồi (vi/en)
X-Request-ID: <unique_request_id>    # ID request để trace
```

### Common Parameters

#### Query Parameters
- `page`: Số trang (default: 1)
- `per_page`: Số item mỗi trang (default: 10, max: 100)
- `sort`: Sắp xếp (asc/desc)
- `search`: Từ khóa tìm kiếm
- `from_date`: Lọc từ ngày (format: Y-m-d)
- `to_date`: Lọc đến ngày (format: Y-m-d)

#### Filtering
```http
/api/resource?filter[field]=value
Example: /api/news?filter[competition_id]=2021&filter[team_id]=1
```

#### Including Related Data
```http
/api/resource?include=relation1,relation2
Example: /api/news/1?include=comments,teams
```

## Example Requests & Responses

### Authentication

#### Register
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

Response:
```json
{
    "status": true,
    "message": "Đăng ký thành công",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-04-28T10:00:00Z"
        }
    }
}
```

### News

#### Get Latest News
```http
GET /api/news?page=1&per_page=10&competition_id=2021
```

Response:
```json
{
    "status": true,
    "data": {
        "news": [
            {
                "id": 1,
                "title": "Tiêu đề bài viết",
                "source": "BBC Sport",
                "content": "Nội dung bài viết...",
                "thumbnail": "https://example.com/image.jpg",
                "published_at": "2025-04-28T10:00:00Z",
                "competition": {
                    "id": 2021,
                    "name": "Premier League",
                    "emblem": "https://example.com/pl.png"
                },
                "teams": [
                    {
                        "id": 1,
                        "name": "Manchester United",
                        "crest": "https://example.com/manutd.png"
                    }
                ],
                "comments_count": 5,
                "is_saved": false
            }
        ],
        "meta": {
            "current_page": 1,
            "per_page": 10,
            "total": 100,
            "last_page": 10
        }
    }
}
```

### Error Responses

#### Authentication Error
```json
{
    "status": false,
    "message": "Unauthorized",
    "error": {
        "code": 401,
        "type": "AuthenticationException"
    }
}
```

#### Validation Error
```json
{
    "status": false,
    "message": "The given data was invalid",
    "errors": {
        "email": [
            "The email field is required.",
            "The email must be a valid email address."
        ],
        "password": [
            "The password field is required.",
            "The password must be at least 8 characters."
        ]
    }
}
```

#### Resource Not Found
```json
{
    "status": false,
    "message": "Resource not found",
    "error": {
        "code": 404,
        "type": "ModelNotFoundException"
    }
}
```

#### Rate Limit Exceeded
```json
{
    "status": false,
    "message": "Too Many Attempts",
    "error": {
        "code": 429,
        "type": "ThrottleRequestsException",
        "retry_after": 60
    }
}
```
