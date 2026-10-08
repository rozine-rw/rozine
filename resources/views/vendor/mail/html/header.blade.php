@props(['url', 'audience' => 'account'])
@php
$eyebrow = match ($audience) {
    'investor' => __('For investors'),
    'business' => __('For business'),
    'auditor' => __('Audit Partner'),
    'admin' => __('Operations'),
    default => null,
};
@endphp
<tr>
<td class="header">
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-tile brand-tile-{{ $audience }}" width="36" height="36">
<a href="{{ $url }}"><img src="{{ asset('images/rozine-star-white.png') }}" width="20" height="20" alt="" border="0"></a>
</td>
<td class="brand-name">
<a href="{{ $url }}">{{ $slot }}</a>
@if ($eyebrow !== null)
<p class="brand-eyebrow">{{ $eyebrow }}</p>
@endif
</td>
</tr>
</table>
</td>
</tr>
