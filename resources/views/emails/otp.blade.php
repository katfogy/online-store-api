@component('mail::message')
# Verify Your Email

Your One-Time Password (OTP) is:

# **{{ $otpCode }}**

This OTP is valid for 10 minutes.  
Please do not share it with anyone.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
