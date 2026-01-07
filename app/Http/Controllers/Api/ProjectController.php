<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('items')
            ->withCount(['items', 'highRiskItems', 'pendingItems'])
            ->latest()
            ->paginate(15);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,archived,completed',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated['user_id'] = $userId;
        $validated['status'] = $validated['status'] ?? 'active';

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load('items'),
        ], 201);
    }

    public function show(Project $project)
    {
        $project->load('items');

        return response()->json([
            'project' => $project,
            'statistics' => $project->riskStatistics,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,archived,completed',
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->fresh()->load('items'),
        ]);
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }
}
