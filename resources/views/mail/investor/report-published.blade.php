<x-mail::message audience="investor">
# {{ __('New monthly report') }}

<x-mail::notice tone="success">
{{ __('Verified · co-signed by Audit Partner :partner', ['partner' => $auditPartner]) }}
</x-mail::notice>

{{ __('The :period report for :business is published. It carries the parsed figures, both parties\' notes, on-site photographs and the verification seal.', ['period' => $period, 'business' => $businessName]) }}

<x-mail::button :url="$url" color="investor">
{{ __('Read the report') }}
</x-mail::button>
</x-mail::message>
