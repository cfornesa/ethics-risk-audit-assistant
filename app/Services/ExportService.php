<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\View;

class ExportService
{
    public function exportProjectAsMarkdown(Project $project): string
    {
        $project->load(['items' => function ($query) {
            $query->orderBy('risk_score', 'desc');
        }]);

        $stats = $project->riskStatistics;

        $md = "# Ethics/Risk Audit Report\n\n";
        $md .= "## Project: {$project->name}\n\n";
        $md .= "**Generated:** " . now()->format('Y-m-d H:i:s') . "\n\n";

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

    public function exportProjectAsHtml(Project $project): string
    {
        $project->load(['items' => function ($query) {
            $query->orderBy('risk_score', 'desc');
        }]);

        $data = [
            'project' => $project,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'stats' => $project->riskStatistics,
        ];

        return View::make('projects.export', $data)->render();
    }
}
