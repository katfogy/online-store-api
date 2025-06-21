@component('mail::message')
# OTP Verification

Your OTP is: **{{ $otpCode }}**

This OTP will expire in 10 minutes.

Thanks,  
{{ config('app.name') }}
@endcomponent
