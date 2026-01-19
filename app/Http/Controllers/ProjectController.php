<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ExportService;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {
    }

    public function index()
    {
        $projects = Project::with('items')
            ->withCount(['items', 'highRiskItems', 'pendingItems'])
            ->latest()
            ->paginate(15);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'active';

        $project = Project::create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load([
            'items' => function ($query) {
                $query->latest();
            }
        ]);

        $stats = [
            'total_items' => $project->items->count(),
            'low_risk' => $project->items->where('risk_level', 'low')->count(),
            'medium_risk' => $project->items->where('risk_level', 'medium')->count(),
            'high_risk' => $project->items->where('risk_level', 'high')->count(),
            'critical_risk' => $project->items->where('risk_level', 'critical')->count(),
            'pending' => $project->items->where('status', 'pending')->count(),
            'completed' => $project->items->where('status', 'completed')->count(),
            'requires_review' => $project->items->where('requires_human_review', true)->count(),
        ];

        return view('projects.show', compact('project', 'stats'));
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    public function export(Project $project, string $format = 'html')
    {
        if ($format === 'markdown') {
            $content = $this->exportService->exportProjectAsMarkdown($project);
            $filename = str_replace(' ', '-', strtolower($project->name)) . '-export.md';

            return response($content)
                ->header('Content-Type', 'text/markdown')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        $content = $this->exportService->exportProjectAsHtml($project);

        return response($content)->header('Content-Type', 'text/html');
    }
}
