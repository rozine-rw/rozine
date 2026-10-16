<x-mail::message audience="business">
@switch ($outcome)
@case ('approved')
# {{ __('Application approved') }}

<x-mail::notice tone="success">
{{ __('Approved for listing') }}
</x-mail::notice>

{{ __('Your application for :business is approved. We will tell you when your raise goes live to investors.', ['business' => $businessName]) }}
@break
@case ('returned')
# {{ __('Your application needs changes') }}

<x-mail::notice tone="warning">
{{ __('Returned: :reason', ['reason' => $reason]) }}
</x-mail::notice>

{{ __('Update your application for :business in the Business app and submit it again.', ['business' => $businessName]) }}
@break
@default
# {{ __('Your application was not approved') }}

<x-mail::notice tone="danger">
{{ __('Declined: :reason', ['reason' => $reason]) }}
</x-mail::notice>

{{ __('The Business app shows the figures we used, the shortfall, and what would make :business qualify.', ['business' => $businessName]) }}
@endswitch

<x-mail::details :rows="$details" />

<x-mail::button :url="$url" color="business">
{{ __('Open application') }}
</x-mail::button>
</x-mail::message>
