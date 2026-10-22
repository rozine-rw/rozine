<x-mail::message audience="business">
# {{ __('Time for your monthly report') }}

{{ __('The :period audit for :business has started. Your Audit Partner seals the report by :date.', ['business' => $businessName, 'period' => $period, 'date' => $closesOn]) }}

<x-mail::panel>
1. {{ __('Check the audit prep list and have the month\'s statements and records ready.') }}
2. {{ __('Your Audit Partner verifies them and seals the report.') }}
3. {{ __('Review the sealed report, then co-sign it or raise a dispute.') }}
</x-mail::panel>

{{ __('Reporting on time keeps your standing healthy. A missed report can stop you listing new raises.') }}

<x-mail::button :url="$url" color="business">
{{ __('Open audit prep') }}
</x-mail::button>
</x-mail::message>
