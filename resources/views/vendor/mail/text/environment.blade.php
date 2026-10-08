@props(['profile'])
{{ $profile === 'demo' ? __('Demo — not live') : __('UAT — not live') }}: {{ __('Use synthetic data only. No real-money transactions.') }}
