<x-mail::message audience="business">
# {{ __('Your raise is funded') }}

{{ __('Investors fully funded :business. We sent :amount to :account.', ['business' => $businessName, 'amount' => $amount, 'account' => $account]) }}

<x-mail::details :rows="$details" />

<x-mail::button :url="$url" color="business">
{{ __('View repayment schedule') }}
</x-mail::button>
</x-mail::message>
