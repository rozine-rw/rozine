<x-mail::message audience="investor">
# {{ __('Payout received') }}

{{ __(':total from :business is in your wallet. This is payment :number of :count.', ['total' => $total, 'business' => $businessName, 'number' => $paymentNumber, 'count' => $paymentCount]) }}

<x-mail::details :rows="$details" />

<x-mail::button :url="$url" color="investor">
{{ __('View holding') }}
</x-mail::button>
</x-mail::message>
