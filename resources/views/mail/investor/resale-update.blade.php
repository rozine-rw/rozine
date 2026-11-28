<x-mail::message audience="investor">
@if ($event === 'reopened')
# {{ __('Resale has reopened') }}

{{ __('Compliance lifted the halt on :scope. You can list notes and buy listed notes again. Orders the halt cancelled stay cancelled, so list again if you still want to sell.', ['scope' => $scope]) }}
@elseif ($event === 'reservation_released')
# {{ __('Your reservation was released') }}

{{ trans_choice('Compliance halted resale of :title, so your reservation of :units note ended. The cash it held is back in your wallet.|Compliance halted resale of :title, so your reservation of :units notes ended. The cash it held is back in your wallet.', (int) $units, ['title' => $title, 'units' => $units]) }}
@elseif ($event === 'listing_halted')
# {{ __('Your listing was cancelled') }}

{{ trans_choice('Compliance halted resale of :title, so your listing of :units note was cancelled. You still hold the note.|Compliance halted resale of :title, so your listing of :units notes was cancelled. You still hold the notes.', (int) $units, ['title' => $title, 'units' => $units]) }}
@else
# {{ __('Your listing was cancelled') }}

{{ trans_choice('Compliance cancelled your listing of :units note of :title. You still hold the note.|Compliance cancelled your listing of :units notes of :title. You still hold the notes.', (int) $units, ['title' => $title, 'units' => $units]) }}
@endif

<x-mail::notice :tone="$event === 'reopened' ? 'success' : 'warning'">
{{ __('Reason: :reason', ['reason' => $reason]) }}
</x-mail::notice>

<x-mail::button :url="$url" color="investor">
{{ __('Open the Market') }}
</x-mail::button>
</x-mail::message>
