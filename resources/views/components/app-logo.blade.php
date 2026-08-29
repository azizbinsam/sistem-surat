@props(['class' => 'font-bold text-lg'])

@php
    $settings = \App\Models\AppSettings::current();
@endphp

@if ($settings->logo_aplikasi)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_aplikasi) }}"
        alt="{{ $settings->nama_aplikasi }}" {{ $attributes->merge(['class' => 'h-8 w-auto']) }}>
@else
    <span {{ $attributes->merge(['class' => $class]) }}>{{ $settings->nama_aplikasi }}</span>
@endif
