<!DOCTYPE html>
<html>

<head>
    <title>Đặt lại mật khẩu</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .content {
            padding: 20px 0;
        }

        .otp-code {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            background-color: #f5f5f5;
            margin: 15px 0;
            letter-spacing: 5px;
            border-radius: 5px;
        }

        .footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Đặt lại mật khẩu</h2>
        </div>
        <div class="content">
            <p>Xin chào!</p>
            <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình. Vui lòng sử dụng mã OTP dưới đây để hoàn tất quá
                trình:</p>

            <div class="otp-code">{{ $otp }}</div>

            <p>Mã OTP này sẽ hết hạn sau 10 phút.</p>

            <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này hoặc liên hệ với chúng tôi nếu bạn có bất
                kỳ câu hỏi nào.</p>

            <p>Trân trọng,<br>Đội ngũ Football App</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Football App. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>

</html>
