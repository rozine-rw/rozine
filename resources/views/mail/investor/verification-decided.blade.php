<x-mail::message audience="investor">
@if ($approved)
# {{ __('You are verified') }}

<x-mail::notice tone="success">
{{ __('KYC status: Verified') }}
</x-mail::notice>

{{ __('Compliance has verified your identity. Your wallet is ready: deposit to start investing in audited Rwandan businesses.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Start exploring') }}
</x-mail::button>
@else
# {{ __('Your details need another look') }}

<x-mail::notice tone="warning">
{{ __('Compliance could not verify these details: :reason', ['reason' => $reason]) }}
</x-mail::notice>

{{ __('Correct them in the Investor app and submit again. Investing stays paused until your identity is verified.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Update your details') }}
</x-mail::button>
@endif
</x-mail::message>
