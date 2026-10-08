<x-mail::message audience="investor">
# {{ __('Investment confirmed') }}

{{ __('You invested :amount in :business. It is now in your portfolio.', ['amount' => $amount, 'business' => $businessName]) }}

<x-mail::details :rows="$details" />

<x-mail::notice tone="warning">
{{ __('Returns are projected, not guaranteed. You can lose some or all of the money you invest.') }}
</x-mail::notice>

<x-mail::button :url="$url" color="investor">
{{ __('View in portfolio') }}
</x-mail::button>
</x-mail::message>
