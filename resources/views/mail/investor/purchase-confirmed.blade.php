<x-mail::message audience="investor">
# {{ __('Purchase confirmed') }}

{{ __('You committed :amount to :business. A confirmed commitment is final. If the raise does not fill, your money comes back to your wallet.', ['amount' => $amount, 'business' => $businessName]) }}

<x-mail::details :rows="$details" />

<x-mail::notice tone="warning">
{{ __('Returns are projected, not guaranteed. You can lose some or all of the money you invest.') }}
</x-mail::notice>

<x-mail::button :url="$url" color="investor">
{{ __('View commitment') }}
</x-mail::button>
</x-mail::message>
