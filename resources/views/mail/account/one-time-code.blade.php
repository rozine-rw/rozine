<x-mail::message :audience="$audience">
@if ($forBusiness)
# {{ __('Verify your business') }}

{{ __(':business is being registered on Rozine. Enter this code in the Business app to confirm you act for it. We sent it to the email registered for this business at the Rwanda Development Board.', ['business' => $businessName]) }}
@else
# {{ __('Confirm your email') }}

{{ __('Enter this code in the Rozine app to confirm your email address and finish creating your account.') }}
@endif

<x-mail::code :code="$code" :hint="__('Expires in :count minutes. Never share it — Rozine staff will never ask for it.', ['count' => $expiresInMinutes])" />

{{ __('Did not ask for this code? You can ignore this email. Nothing changes until the code is entered.') }}
</x-mail::message>
