@props(['tone' => 'neutral'])
<table class="notice notice-{{ $tone }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="notice-content">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
