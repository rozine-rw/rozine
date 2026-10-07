<x-mail::message audience="business">
@if ($overdue)
# {{ __('Your repayment is overdue') }}

<x-mail::notice tone="danger">
{{ $overdueLabel }}
</x-mail::notice>

{{ __('The :amount repayment for :business was due on :date. Pay it now to protect your rating and standing; investors see late payments on their holdings.', ['amount' => $amount, 'business' => $businessName, 'date' => $dueOn]) }}
@else
# {{ __('Repayment coming up') }}

{{ __('The :amount repayment for :business is due on :date. Make sure your wallet holds enough to cover it.', ['amount' => $amount, 'business' => $businessName, 'date' => $dueOn]) }}
@endif

<x-mail::details :rows="$details" />

<x-mail::button :url="$url" color="business">
{{ __('Pay instalment') }}
</x-mail::button>
</x-mail::message>
