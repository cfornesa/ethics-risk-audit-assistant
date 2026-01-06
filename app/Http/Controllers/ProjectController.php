<?php

namespace App\Http\Controllers;

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

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,archived,completed',
        ]);

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

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,archived,completed',
        ]);

        $project->update($validated);

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
        $project->load(['items' => function ($query) {
            $query->orderBy('risk_score', 'desc');
        }]);

        $data = [
            'project' => $project,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'stats' => $project->riskStatistics,
        ];

        if ($format === 'markdown') {
            return response($this->generateMarkdownExport($data))
                ->header('Content-Type', 'text/markdown')
                ->header('Content-Disposition', "attachment; filename=\"{$project->name}-export.md\"");
        }

        return view('projects.export', $data);
    }

    protected function generateMarkdownExport(array $data): string
    {
        $project = $data['project'];
        $stats = $data['stats'];

        $md = "# Ethics/Risk Audit Report\n\n";
        $md .= "## Project: {$project->name}\n\n";
        $md .= "**Generated:** {$data['export_date']}\n\n";

        if ($project->description) {
            $md .= "**Description:** {$project->description}\n\n";
        }

        $md .= "## Summary Statistics\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Total Items | {$stats['total']} |\n";
        $md .= "| Low Risk | {$stats['low']} |\n";
        $md .= "| Medium Risk | {$stats['medium']} |\n";
        $md .= "| High Risk | {$stats['high']} |\n";
        $md .= "| Critical Risk | {$stats['critical']} |\n";
        $md .= "| Pending Review | {$stats['pending']} |\n";
        $md .= "| Completed | {$stats['completed']} |\n\n";

        $md .= "## Items\n\n";

        foreach ($project->items as $item) {
            $md .= "### {$item->title}\n\n";
            $md .= "- **Risk Score:** {$item->risk_score}/100\n";
            $md .= "- **Risk Level:** " . strtoupper($item->risk_level) . "\n";
            $md .= "- **Status:** {$item->status}\n";
            $md .= "- **Content Type:** {$item->content_type}\n";
            $md .= "- **Audited:** " . ($item->audited_at ? $item->audited_at->format('Y-m-d H:i:s') : 'Not audited') . "\n\n";

            if ($item->risk_summary) {
                $md .= "**Risk Summary:**\n\n{$item->risk_summary}\n\n";
            }

            if ($item->risk_breakdown) {
                $md .= "**Risk Breakdown:**\n\n";
                foreach ($item->risk_breakdown as $category => $details) {
                    $score = $details['score'] ?? 0;
                    $md .= "- **" . ucwords(str_replace('_', ' ', $category)) . ":** {$score}/10\n";
                    if (!empty($details['issues'])) {
                        foreach ($details['issues'] as $issue) {
                            $md .= "  - {$issue}\n";
                        }
                    }
                }
                $md .= "\n";
            }

            if ($item->mitigation_suggestions && count($item->mitigation_suggestions) > 0) {
                $md .= "**Mitigation Suggestions:**\n\n";
                foreach ($item->mitigation_suggestions as $suggestion) {
                    $md .= "- {$suggestion}\n";
                }
                $md .= "\n";
            }

            $md .= "---\n\n";
        }

        return $md;
    }
}
