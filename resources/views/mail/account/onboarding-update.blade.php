<x-mail::message>
@php
$what = match ($role) {
    'business' => $company !== null ? __('your application for :company', ['company' => $company]) : __('your business application'),
    'institution' => $company !== null ? __('your institutional investor application for :company', ['company' => $company]) : __('your institutional investor application'),
    default => __('your Auditor application'),
};
@endphp
@if ($event === 'kyc_received')
# {{ __('We have your identity check') }}

<x-mail::notice tone="info">
{{ __('KYC status: With Compliance') }}
</x-mail::notice>

{{ __('Compliance usually reviews it within 1 working day. We will email you as soon as it is decided. You can follow it on your dashboard.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Open your dashboard') }}
</x-mail::button>
@elseif ($event === 'role_received')
# {{ __('We have :what', ['what' => $what]) }}

<x-mail::notice tone="info">
{{ __('Status: With Compliance') }}
</x-mail::notice>

{{ __('Compliance reviews it once your own identity check is approved. We will email you as soon as it is decided.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Open your dashboard') }}
</x-mail::button>
@elseif ($event === 'role_returned')
# {{ __('Compliance needs a few changes') }}

<x-mail::notice tone="warning">
{{ __('Compliance sent back :what. Change the items below, then send it again.', ['what' => $what]) }}
</x-mail::notice>

@foreach ($items as $item)
- **{{ $item['label'] }}**@if ($item['note'] !== ''): {{ $item['note'] }}@endif

@endforeach

@if ($note !== null && $note !== '')
{{ __('Note from Compliance: :note', ['note' => $note]) }}
@endif

{{ __('You can still change any other answer before you send it again.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Make the changes') }}
</x-mail::button>
@elseif ($event === 'role_approved' && $role === 'institution')
# {{ __('You now invest for :company', ['company' => $company]) }}

<x-mail::notice tone="success">
{{ __('Institutional investor status: Verified') }}
</x-mail::notice>

{{ __('Compliance approved :what. You stay an Investor and now invest for the institution, from its own bank account.', ['what' => $what]) }}

{{ __('Next, open Investor to fund the institution’s wallet and invest. Auto-Deploy, which runs the monthly distribution under the institution’s own rules, comes later. Rozine does not recommend businesses.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Open Investor') }}
</x-mail::button>
@elseif ($event === 'role_approved')
# {{ $role === 'business' ? __('Your business is approved') : __('You are an active Audit Partner') }}

<x-mail::notice tone="success">
{{ $role === 'business' ? __('Business status: Verified') : __('ICPAR licence verified · Active') }}
</x-mail::notice>

{{ $role === 'business'
    ? __('Compliance approved :what. Your account is now a Business account, so you can apply for funding.', ['what' => $what])
    : __('Compliance approved your ICPAR licence. Your account is now an Auditor account, so you can receive audit jobs.') }}

<x-mail::button :url="$url" color="investor">
{{ $role === 'business' ? __('Open Business') : __('Open Auditor') }}
</x-mail::button>
@else
# {{ __('Your application was not approved') }}

<x-mail::notice tone="warning">
{{ __('Compliance could not approve :what: :note', ['what' => $what, 'note' => $note]) }}
</x-mail::notice>

{{ __('Your Investor account is unchanged. You can correct your details and apply again from your dashboard.') }}

<x-mail::button :url="$url" color="investor">
{{ __('Open your dashboard') }}
</x-mail::button>
@endif
</x-mail::message>
