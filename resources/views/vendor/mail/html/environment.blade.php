@props(['profile'])
<p><strong>{{ $profile === 'demo' ? __('Demo — not live') : __('UAT — not live') }}</strong> · {{ __('Use synthetic data only. No real-money transactions.') }}</p>
