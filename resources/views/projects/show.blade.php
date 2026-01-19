@extends('layouts.app')

@section('title', $project->name . ' - Ethics/Risk Audit Assistant')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
                @if ($project->description)
                    <p class="mt-1 text-sm text-gray-600">{{ $project->description }}</p>
                @endif
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('projects.edit', $project) }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Edit Project
                </a>
                <a href="{{ route('projects.export', [$project, 'html']) }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Export HTML
                </a>
                <a href="{{ route('projects.export', [$project, 'markdown']) }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Export Markdown
                </a>
                <a href="{{ route('items.create', ['project_id' => $project->id]) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Add Item
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_items'] }}</dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Critical Risk</dt>
                <dd class="mt-1 text-3xl font-semibold text-red-600">{{ $stats['critical_risk'] }}</dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">High Risk</dt>
                <dd class="mt-1 text-3xl font-semibold text-orange-600">{{ $stats['high_risk'] }}</dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Requires Review</dt>
                <dd class="mt-1 text-3xl font-semibold text-yellow-600">{{ $stats['requires_review'] }}</dd>
            </div>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Items
            </h3>
        </div>

        @if ($project->items->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No items</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding an item to audit.</p>
                <div class="mt-6">
                    <a href="{{ route('items.create', ['project_id' => $project->id]) }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Add Item
                    </a>
                </div>
            </div>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach ($project->items as $item)
                    <li>
                        <a href="{{ route('items.show', $item) }}" class="block hover:bg-gray-50">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            {{ $item->title }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ Str::limit($item->content, 100) }}
                                        </p>
                                    </div>
                                    <div class="ml-2 flex-shrink-0 flex space-x-2">
                                        @if ($item->risk_level)
                                            <x-risk-badge :level="$item->risk_level" />
                                        @endif
                                        <x-status-badge :status="$item->status" />
                                        @if ($item->requires_human_review)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Needs Review
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if ($item->risk_score !== null)
                                    <div class="mt-2">
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-500 mr-2">Risk Score:</span>
                                            <div class="flex-1 bg-gray-200 rounded-full h-2.5 max-w-xs">
                                                @php
                                                    $percentage = $item->risk_score;
                                                    $colorClass = match($item->risk_level) {
                                                        'critical' => 'bg-red-600',
                                                        'high' => 'bg-orange-500',
                                                        'medium' => 'bg-yellow-500',
                                                        'low' => 'bg-green-500',
                                                        default => 'bg-gray-500',
                                                    };
                                                @endphp
                                                <div class="{{ $colorClass }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span class="ml-2 text-sm font-medium text-gray-900">{{ $item->risk_score }}/100</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
