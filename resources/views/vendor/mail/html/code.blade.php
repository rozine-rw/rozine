@props(['code', 'label' => null, 'hint' => null])
<table class="code" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="code-label">{{ $label ?? __('One-time code') }}</td>
</tr>
<tr>
<td class="code-value">{{ $code }}</td>
</tr>
@if ($hint !== null)
<tr>
<td class="code-hint">{{ $hint }}</td>
</tr>
@endif
</table>
