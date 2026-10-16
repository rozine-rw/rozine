<x-mail::message audience="investor">
# {{ __('Your money is back') }}

{{ __('The :business raise did not reach its target within 30 days, so it will not go ahead. The :refund you committed is back in your wallet in full, with no fee.', ['business' => $businessName, 'refund' => $refund]) }}

<x-mail::details :rows="$details" />

<x-mail::button :url="$url" color="investor">
{{ __('Explore other deals') }}
</x-mail::button>
</x-mail::message>
