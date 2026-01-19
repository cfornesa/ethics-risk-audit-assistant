@extends('layouts.app')

@section('title', 'Edit Project - Ethics/Risk Audit Assistant')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Edit Project</h1>
        <p class="mt-1 text-sm text-gray-600">Update your project's information.</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('projects.update', $project) }}" method="POST" class="px-6 py-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Project Name *
                    </label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $project->name) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('description', $project->description) }}</textarea>
                    <p class="mt-2 text-sm text-gray-500">
                        Optional description of the project's purpose and scope.
                    </p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">
                        Status
                    </label>
                    <select name="status" id="status"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="active" {{ old('status', $project->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="archived" {{ old('status', $project->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-x-4">
                <a href="{{ route('projects.show', $project) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
