<x-mail::message>
# {{ __('Reset your password') }}

{{ __('We received a request to reset the password for :email. Choose a new one with the button below.', ['email' => $email]) }}

<x-mail::button :url="$url">
{{ __('Reset password') }}
</x-mail::button>

{{ __('This link expires in :count minutes. If you did not ask for a reset, ignore this email and your password stays the same.', ['count' => $expiresInMinutes]) }}

<x-slot:subcopy>
{{ __('Button not working? Paste this link into your browser:') }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
