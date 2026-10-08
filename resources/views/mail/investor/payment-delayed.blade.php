<x-mail::message audience="investor">
# {{ __('A payment is late') }}

<x-mail::notice tone="warning">
{{ __('Payment delayed · :overdue', ['overdue' => $overdue]) }}
</x-mail::notice>

{{ __('The repayment of :amount from :business, due on :date, has not arrived yet.', ['amount' => $amount, 'business' => $businessName, 'date' => $dueOn]) }}

{{ __('Rozine follows up with the business, and your holding page shows each recovery step as it happens.') }}

<x-mail::button :url="$url" color="investor">
{{ __('View holding') }}
</x-mail::button>
</x-mail::message>
