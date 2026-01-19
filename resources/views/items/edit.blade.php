@extends('layouts.app')

@section('title', 'Edit Item - Ethics/Risk Audit Assistant')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Edit Item</h1>
        <p class="mt-1 text-sm text-gray-600">Update the item details. You can re-audit after saving changes.</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('items.update', $item) }}" method="POST" class="px-6 py-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700">
                        Project *
                    </label>
                    <select name="project_id" id="project_id" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $item->project_id) == $project->id ? 'selected' : '' }}>
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
                           value="{{ old('title', $item->title) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="content_type" class="block text-sm font-medium text-gray-700">
                        Content Type *
                    </label>
                    <select name="content_type" id="content_type" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="message" {{ old('content_type', $item->content_type) == 'message' ? 'selected' : '' }}>Message</option>
                        <option value="ad" {{ old('content_type', $item->content_type) == 'ad' ? 'selected' : '' }}>Advertisement</option>
                        <option value="script" {{ old('content_type', $item->content_type) == 'script' ? 'selected' : '' }}>Script</option>
                        <option value="post" {{ old('content_type', $item->content_type) == 'post' ? 'selected' : '' }}>Social Media Post</option>
                        <option value="other" {{ old('content_type', $item->content_type) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">
                        Content *
                    </label>
                    <textarea name="content" id="content" rows="8" required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('content', $item->content) }}</textarea>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Changes to the content will not automatically trigger a re-audit. Use the "Save & Re-Audit" button to queue a new audit.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-x-4">
                <a href="{{ route('items.show', $item) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Changes
                </button>
                <button type="submit" name="reaudit" value="1"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Save & Re-Audit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
