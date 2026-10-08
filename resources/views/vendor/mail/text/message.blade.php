@props(['audience' => 'account'])
@inject('isolation', 'App\Application\Environment\EnvironmentIsolation')
<x-mail::layout>
    {{-- Non-live notice --}}
    @if ($isolation->isIsolated())
        <x-slot:environment>
            <x-mail::environment :profile="$isolation->profile()" />
        </x-slot:environment>
    @endif

    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            Rozine
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
{{ __('Rozine will never ask for your password, PIN or one-time code by email, SMS or phone.') }}
{{ __('Questions? Write to') }} hello@rozine.rw
© {{ date('Y') }} Rozine Technologies Ltd · Kigali, Rwanda
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
