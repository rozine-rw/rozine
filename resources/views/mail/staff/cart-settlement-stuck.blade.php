<x-mail::message>
# {{ __('A cart checkout could not be released') }}

<x-mail::notice tone="warning">
{{ __('An Investor\'s whole-cart hold passed its deadline, but expiring it failed. Their cash stays held until it is released.') }}
</x-mail::notice>

<x-mail::details :rows="$details" />

{{ __('Open Exceptions in the admin console and retry the release. It releases every line of the cart together, never one on its own.') }}

<x-mail::button :url="$url">
{{ __('Open Exceptions') }}
</x-mail::button>
</x-mail::message>
