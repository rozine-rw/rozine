@props(['code', 'label' => null, 'hint' => null])
{{ $label ?? __('One-time code') }}: {{ $code }}
@if ($hint !== null)
{{ $hint }}
@endif
