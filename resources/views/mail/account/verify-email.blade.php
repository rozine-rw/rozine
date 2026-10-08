<x-mail::message>
# {{ __('Verify your email') }}

{{ __('Hi :name, confirm this is your email address to finish setting up your Rozine account. Your apps open once it is verified.', ['name' => $name]) }}

<x-mail::button :url="$url">
{{ __('Verify email address') }}
</x-mail::button>

{{ __('This link expires in :count minutes. If you did not create a Rozine account, you can ignore this email.', ['count' => $expiresInMinutes]) }}

<x-slot:subcopy>
{{ __('Button not working? Paste this link into your browser:') }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
