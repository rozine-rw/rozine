<x-mail::message audience="business">
# {{ __('Time for your monthly report') }}

{{ __('The reporting window for :business is open. Submit your :period report by :date.', ['business' => $businessName, 'period' => $period, 'date' => $closesOn]) }}

<x-mail::panel>
1. {{ __('Upload the statement for the period.') }}
2. {{ __('Add a short note, up to 100 characters.') }}
3. {{ __('Add up to five photographs.') }}
4. {{ __('Submit it for your Audit Partner\'s co-signature.') }}
</x-mail::panel>

{{ __('Reporting on time keeps your standing healthy. A missed report can stop you listing new raises.') }}

<x-mail::button :url="$url" color="business">
{{ __('Start report') }}
</x-mail::button>
</x-mail::message>
