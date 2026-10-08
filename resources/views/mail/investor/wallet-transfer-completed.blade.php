<x-mail::message audience="investor">
@if ($deposit)
# {{ __('Deposit received') }}

{{ __(':amount from :account is in your Rozine wallet.', ['amount' => $amount, 'account' => $account]) }}
@else
# {{ __('Withdrawal sent') }}

{{ __(':amount is on its way to :account.', ['amount' => $amount, 'account' => $account]) }}
@endif

<x-mail::details :rows="$details" />

<x-mail::button :url="$url" color="investor">
{{ __('Open wallet') }}
</x-mail::button>

{{ __('Did not make this transaction? Write to hello@rozine.rw straight away.') }}
</x-mail::message>
