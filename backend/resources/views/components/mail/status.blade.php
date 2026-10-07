@props(['status', 'label'])
@php
    [$bg, $fg] = match ($status) {
        'in_lavorazione' => ['#e4eefb', '#2b5b92'],
        'evaso' => ['#e2f3e8', '#25683f'],
        'annullato' => ['#fbe3e6', '#9b2c3d'],
        default => ['#fdf1dc', '#8a5a14'],
    };
@endphp
<span style="display:inline-block; background-color:{{ $bg }}; color:{{ $fg }}; padding:6px 16px; border-radius:999px; font-size:13px; font-weight:bold;">{{ $label }}</span>
