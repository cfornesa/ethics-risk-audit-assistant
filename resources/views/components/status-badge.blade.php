@props(['status'])

@php
    $colors = [
        'pending' => 'bg-gray-100 text-gray-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        'requires_review' => 'bg-orange-100 text-orange-800',
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$color}"]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
