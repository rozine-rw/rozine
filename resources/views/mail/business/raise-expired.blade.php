<x-mail::message audience="business">
# {{ __('Your raise has closed') }}

{{ __('The raise for :business did not reach its target within 30 days, so it will not go ahead. Investors have their commitments back in full, and you pay no fee.', ['business' => $businessName]) }}

<x-mail::details :rows="$details" />

{{ __('You can review your figures in the Business app and apply again.') }}

<x-mail::button :url="$url" color="business">
{{ __('Open Business app') }}
</x-mail::button>
</x-mail::message>
