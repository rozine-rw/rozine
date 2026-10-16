@props(['rows'])
<table class="details" width="100%" cellpadding="0" cellspacing="0" role="presentation">
@foreach ($rows as $label => $value)
<tr>
<td @class(['details-label', 'details-last' => $loop->last])>{{ $label }}</td>
<td @class(['details-value', 'details-last' => $loop->last]) align="right">{{ $value }}</td>
</tr>
@endforeach
</table>
