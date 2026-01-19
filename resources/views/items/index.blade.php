@extends('layouts.app')

@section('title', 'All Items - Ethics/Risk Audit Assistant')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">All Items</h1>
        <a href="{{ route('items.create') }}"
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            Create Item
        </a>
    </div>

    @if ($items->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No items</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new item.</p>
            <div class="mt-6">
                <a href="{{ route('items.create') }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Create Item
                </a>
            </div>
        </div>
    @else
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ route('items.show', $item) }}" class="block hover:bg-gray-50">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3">
                                            <p class="text-sm font-medium text-indigo-600 truncate">
                                                {{ $item->title }}
                                            </p>
                                            <span class="text-sm text-gray-500">
                                                in {{ $item->project->name }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                                            {{ $item->content }}
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
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        {{ ucfirst($item->content_type) }}
                                    </div>
                                    @if ($item->risk_score !== null)
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-500 mr-2">Risk Score:</span>
                                            <span class="text-sm font-medium text-gray-900">{{ $item->risk_score }}/100</span>
                                        </div>
                                    @endif
                                    <div class="text-sm text-gray-500">
                                        {{ $item->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            {{ $items->links() }}
        </div>
    @endif
</div>
@endsection
