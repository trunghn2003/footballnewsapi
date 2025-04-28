# RAG (Retrieval-Augmented Generation) API Documentation

## Giới thiệu
RAG API là hệ thống trí tuệ nhân tạo tích hợp vào ứng dụng bóng đá, cho phép người dùng đặt câu hỏi và nhận câu trả lời thông minh về bóng đá dựa trên dữ liệu thực từ hệ thống.

## Cách Sử Dụng

### 1. Authentication
Tất cả các API đều yêu cầu JWT token:
```http
Authorization: Bearer <your_jwt_token>
```

### 2. Endpoints

#### 2.1. Đặt Câu Hỏi
```http
POST /api/rag/ask

Body:
{
    "question": "Cho tôi biết về Manchester United?",
    "type": "team"  // Tùy chọn: news, team, competition
}
```

Response:
```json
{
    "status": true,
    "data": {
        "question": "Cho tôi biết về Manchester United?",
        "answer": "Manchester United là một câu lạc bộ bóng đá chuyên nghiệp..."
    }
}
```

#### 2.2. Index Dữ Liệu
```http
POST /api/rag/index
```
Index toàn bộ dữ liệu vào hệ thống RAG (thường chỉ admin sử dụng).

### 3. Các Loại Câu Hỏi Được Hỗ Trợ

#### 3.1. Về Đội Bóng
- Thông tin chung về đội bóng
  ```
  "Cho tôi biết về Manchester United?"
  "Liverpool có lịch sử như thế nào?"
  ```
- Thông tin về cầu thủ
  ```
  "Ai là đội trưởng của Chelsea?"
  "Số áo của Haaland là gì?"
  ```
- Thành tích
  ```
  "Arsenal đã vô địch Premier League bao nhiêu lần?"
  "Manchester City đã giành được những danh hiệu nào mùa trước?"
  ```

#### 3.2. Về Giải Đấu
- Thông tin chung
  ```
  "Premier League là gì?"
  "Champions League tổ chức như thế nào?"
  ```
- Bảng xếp hạng
  ```
  "Đội nào đang dẫn đầu Premier League?"
  "Top 4 Bundesliga hiện tại?"
  ```
- Lịch thi đấu
  ```
  "Khi nào diễn ra trận chung kết Champions League?"
  "Lịch thi đấu Premier League tuần này?"
  ```

#### 3.3. Về Tin Tức
- Tin mới nhất
  ```
  "Có tin tức gì mới về Manchester United?"
  "Tin chuyển nhượng mới nhất?"
  ```
- Tin theo chủ đề
  ```
  "Tin về chấn thương của các cầu thủ?"
  "Có thông tin gì về HLV mới của Liverpool không?"
  ```

### 4. Tips Sử Dụng
1. **Câu Hỏi Rõ Ràng**:
   - Tốt: "Ai là đội trưởng hiện tại của Manchester United?"
   - Chưa tốt: "Cho biết về đội trưởng"

2. **Sử Dụng Type**:
   - Khi hỏi về đội bóng: `type: "team"`
   - Khi hỏi về tin tức: `type: "news"`
   - Khi hỏi về giải đấu: `type: "competition"`

3. **Thời Gian**:
   - Hệ thống sẽ ưu tiên thông tin mới nhất
   - Có thể hỏi về dữ liệu lịch sử

### 5. Xử Lý Lỗi

#### 5.1. Authentication Error
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

#### 5.2. Validation Error
```json
{
    "status": false,
    "message": "The given data was invalid",
    "errors": {
        "question": ["The question field is required"]
    }
}
```

### 6. Code Examples

#### 6.1. JavaScript/TypeScript
```typescript
const askQuestion = async (question: string, type?: string) => {
  try {
    const response = await axios.post('/api/rag/ask', {
      question,
      type
    }, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    return response.data.data.answer;
  } catch (error) {
    console.error('Error asking question:', error);
    throw error;
  }
};
```

#### 6.2. PHP
```php
public function askQuestion($question, $type = null)
{
    $response = Http::withToken($token)
        ->post('/api/rag/ask', [
            'question' => $question,
            'type' => $type
        ]);

    if ($response->successful()) {
        return $response->json()['data']['answer'];
    }

    throw new Exception($response->json()['message']);
}
```

### 7. Giới Hạn và Lưu Ý
1. Rate Limiting: 60 requests/minute
2. Maximum question length: 500 characters
3. Thời gian phản hồi: 2-5 giây
4. Dữ liệu được cập nhật real-time với database

### 8. Các Trường Hợp Đặc Biệt
1. **Câu hỏi không rõ ràng**: Hệ thống sẽ yêu cầu làm rõ
2. **Thông tin không có trong database**: Hệ thống sẽ thông báo
3. **Nhiều câu hỏi trong một**: Hệ thống sẽ trả lời từng phần

### 9. Bảo Mật
1. Tất cả requests phải có JWT token
2. Dữ liệu nhạy cảm được lọc khỏi câu trả lời
3. Rate limiting để prevent abuse
4. Logging cho security audit

### 10. Performance
1. Caching cho câu hỏi phổ biến
2. Parallel processing cho large-scale indexing
3. Optimized vector search
4. Real-time updates
