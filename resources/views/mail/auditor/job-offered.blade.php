<x-mail::message audience="auditor">
@if ($flash)
# {{ __('Flash Audit offered') }}

<x-mail::notice tone="danger">
{{ __('Flash Audit · runs on a 24-hour clock') }}
</x-mail::notice>

{{ __('You have been offered a Flash Audit of :business in :location. Respond by :time; if you do not, the job is offered to another Audit Partner.', ['business' => $businessName, 'location' => $location, 'time' => $respondBy]) }}
@else
# {{ __('New audit job') }}

{{ __('You have been offered a routine audit of :business in :location. Respond by :time; if you do not, the job is offered to another Audit Partner.', ['business' => $businessName, 'location' => $location, 'time' => $respondBy]) }}
@endif

<x-mail::details :rows="$details" />

{{ __('If you have a conflict of interest, declare it in the Auditor app and decline the job.') }}

<x-mail::button :url="$url" color="auditor">
{{ __('Review job') }}
</x-mail::button>
</x-mail::message>
