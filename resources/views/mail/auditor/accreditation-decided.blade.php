<x-mail::message audience="auditor">
@if ($approved)
# {{ __('You are an active Audit Partner') }}

<x-mail::notice tone="success">
{{ __('ICPAR licence verified · Active') }}
</x-mail::notice>

{{ __('Your accreditation is verified. You can now receive audit jobs within your dispatch radius.') }}

<x-mail::button :url="$url" color="auditor">
{{ __('Open Auditor app') }}
</x-mail::button>
@else
# {{ __('Your accreditation needs another look') }}

<x-mail::notice tone="warning">
{{ __('We could not verify your accreditation: :reason', ['reason' => $reason]) }}
</x-mail::notice>

{{ __('Update your ICPAR details in the Auditor app and submit them again. You cannot receive jobs until your accreditation is active.') }}

<x-mail::button :url="$url" color="auditor">
{{ __('Update your details') }}
</x-mail::button>
@endif
</x-mail::message>
