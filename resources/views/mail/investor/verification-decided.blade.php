<x-mail::message audience="investor">
@if ($approved)
# {{ __('You are verified') }}

<x-mail::notice tone="success">
{{ __('KYC status: Verified') }}
</x-mail::notice>

@if ($purpose === 'auditor')
{{ __('Compliance has verified your identity. Open Rozine and choose Request access on the Auditor card, with your ICPAR licence number, so our team can accredit you.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Request Auditor access') }}
</x-mail::button>
@elseif ($purpose === 'business')
{{ __('Compliance has verified your identity. Open Rozine and choose Request access on the Business card, so our team can link you to your business.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Request Business access') }}
</x-mail::button>
@else
{{ __('Compliance has verified your identity. Your wallet is ready: deposit to start investing in audited Rwandan businesses.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Start exploring') }}
</x-mail::button>
@endif
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
