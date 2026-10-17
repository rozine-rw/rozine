<x-mail::message>
# {{ __('A new device signed in') }}

<x-mail::notice tone="warning">
{{ __(':device signed in to your account on :moment.', ['device' => $device, 'moment' => $moment]) }}
</x-mail::notice>

{{ __('If this was you, there is nothing to do. If it was not, change your password now and contact Rozine support.') }}

<x-mail::button :url="$url">
{{ __('Review your security') }}
</x-mail::button>

{{ __('You get this email because login alerts are on. You can turn them off in your security settings.') }}
</x-mail::message>
