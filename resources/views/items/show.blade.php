@extends('layouts.app')

@section('title', $item->title . ' - Ethics/Risk Audit Assistant')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('projects.show', $item->project) }}" class="text-gray-400 hover:text-gray-500">
                        {{ $item->project->name }}
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-500">{{ $item->title }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <div class="lg:grid lg:grid-cols-3 lg:gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ $item->title }}
                    </h3>
                    <div class="mt-2 flex items-center space-x-2">
                        <x-status-badge :status="$item->status" />
                        @if ($item->risk_level)
                            <x-risk-badge :level="$item->risk_level" />
                        @endif
                        @if ($item->requires_human_review)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Needs Human Review
                            </span>
                        @endif
                    </div>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Content Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($item->content_type) }}</dd>
                        </div>
                        @if ($item->risk_score !== null)
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Risk Score</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $item->risk_score }}/100</dd>
                            </div>
                        @endif
                        @if ($item->audited_at)
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Audited</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $item->audited_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                        @if ($item->llm_model)
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Model</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $item->llm_model }}</dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Content</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $item->content }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if ($item->risk_summary)
                <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Risk Summary</h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <p class="text-sm text-gray-700">{{ $item->risk_summary }}</p>
                    </div>
                </div>
            @endif

            @if ($item->risk_breakdown)
                <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Risk Breakdown</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            @foreach ($item->risk_breakdown as $category => $details)
                                <div class="px-4 py-4 sm:px-6 {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                                    <dt class="text-sm font-medium text-gray-900 flex justify-between items-center">
                                        <span>{{ ucwords(str_replace('_', ' ', $category)) }}</span>
                                        <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $details['score'] >= 8 ? 'bg-red-100 text-red-800' : ($details['score'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                            {{ $details['score'] ?? 0 }}/10
                                        </span>
                                    </dt>
                                    @if (!empty($details['issues']))
                                        <dd class="mt-2 text-sm text-gray-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach ($details['issues'] as $issue)
                                                    <li>{{ $issue }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    @endif
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @endif

            @if ($item->mitigation_suggestions && count($item->mitigation_suggestions) > 0)
                <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Mitigation Suggestions</h3>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <ul class="list-disc list-inside space-y-2 text-sm text-gray-700">
                            @foreach ($item->mitigation_suggestions as $suggestion)
                                <li>{{ $suggestion }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg sticky top-6">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Actions</h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6 space-y-3">
                    @if ($item->status === 'completed' || $item->status === 'failed')
                        <form action="{{ route('items.reaudit', $item) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Re-run Audit
                            </button>
                        </form>
                    @endif

                    @if ($item->requires_human_review)
                        <form action="{{ route('items.mark-reviewed', $item) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Mark as Reviewed
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('items.edit', $item) }}"
                       class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Edit Item
                    </a>

                    <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                            Delete Item
                        </button>
                    </form>
                </div>

                @if ($item->last_error)
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Last Error</h4>
                        <p class="text-sm text-red-600">{{ $item->last_error }}</p>
                        <p class="text-xs text-gray-500 mt-1">Attempts: {{ $item->audit_attempts }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
