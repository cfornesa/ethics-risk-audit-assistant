@extends('layouts.app')

@section('title', 'Create Item - Ethics/Risk Audit Assistant')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Create New Item</h1>
        <p class="mt-1 text-sm text-gray-600">Add new political content for ethics/risk auditing.</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('items.store') }}" method="POST" class="px-6 py-6">
            @csrf

            <div class="space-y-6">
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700">
                        Project *
                    </label>
                    <select name="project_id" id="project_id" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Select a project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $selectedProject) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Title *
                    </label>
                    <input type="text" name="title" id="title" required
                           value="{{ old('title') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500">A brief, descriptive title for this content.</p>
                </div>

                <div>
                    <label for="content_type" class="block text-sm font-medium text-gray-700">
                        Content Type *
                    </label>
                    <select name="content_type" id="content_type" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="message" {{ old('content_type') == 'message' ? 'selected' : '' }}>Message</option>
                        <option value="ad" {{ old('content_type') == 'ad' ? 'selected' : '' }}>Advertisement</option>
                        <option value="script" {{ old('content_type') == 'script' ? 'selected' : '' }}>Script</option>
                        <option value="post" {{ old('content_type') == 'post' ? 'selected' : '' }}>Social Media Post</option>
                        <option value="other" {{ old('content_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">
                        Content *
                    </label>
                    <textarea name="content" id="content" rows="8" required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('content') }}</textarea>
                    <p class="mt-2 text-sm text-gray-500">
                        The full text of the political content to audit. This will be analyzed by the AI ethics auditor.
                    </p>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Once created, this item will be automatically queued for ethics/risk analysis using Mistral AI.
                                You'll receive an email notification if high-risk content is detected.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-x-4">
                <a href="{{ $selectedProject ? route('projects.show', $selectedProject) : route('projects.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create & Audit Item
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
