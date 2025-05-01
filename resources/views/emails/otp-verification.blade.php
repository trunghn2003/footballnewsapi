@component('mail::message')
# Verify Your Email

Thank you for registering with Football News API.

Your OTP verification code is:

@component('mail::panel')
<h1 style="text-align: center; font-size: 32px; letter-spacing: 8px;">{{ $otp }}</h1>
@endcomponent

This code will expire in 10 minutes.

If you didn't request this verification, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
