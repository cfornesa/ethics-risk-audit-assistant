<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethics/Risk Audit Report - {{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            background: #f9fafb;
        }
        .header {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 { color: #1f2937; font-size: 32px; margin-bottom: 10px; }
        h2 { color: #374151; font-size: 24px; margin: 30px 0 15px; }
        h3 { color: #4b5563; font-size: 20px; margin: 20px 0 10px; }
        .meta { color: #6b7280; font-size: 14px; margin-bottom: 10px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-label { font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 32px; font-weight: bold; margin-top: 5px; }
        .stat-low { color: #10b981; }
        .stat-medium { color: #f59e0b; }
        .stat-high { color: #f97316; }
        .stat-critical { color: #ef4444; }
        .item-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-high { background: #fed7aa; color: #9a3412; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .risk-breakdown {
            margin: 20px 0;
            border-left: 4px solid #e5e7eb;
            padding-left: 20px;
        }
        .risk-category {
            margin: 15px 0;
        }
        .risk-category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .risk-category-name {
            font-weight: 600;
            color: #374151;
        }
        .risk-score {
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .score-low { background: #d1fae5; color: #065f46; }
        .score-medium { background: #fef3c7; color: #92400e; }
        .score-high { background: #fed7aa; color: #9a3412; }
        ul { margin: 10px 0 10px 20px; }
        li { margin: 5px 0; color: #4b5563; }
        .mitigation {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .mitigation h4 { color: #1e40af; margin-bottom: 10px; }
        @media print {
            body { background: #fff; }
            .item-card, .stat-card, .header { box-shadow: none; border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ethics/Risk Audit Report</h1>
        <div class="meta">
            <strong>Project:</strong> {{ $project->name }}<br>
            @if ($project->description)
                <strong>Description:</strong> {{ $project->description }}<br>
            @endif
            <strong>Generated:</strong> {{ $export_date }}
        </div>
    </div>

    <h2>Summary Statistics</h2>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Items</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Low Risk</div>
            <div class="stat-value stat-low">{{ $stats['low'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Medium Risk</div>
            <div class="stat-value stat-medium">{{ $stats['medium'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">High Risk</div>
            <div class="stat-value stat-high">{{ $stats['high'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Critical Risk</div>
            <div class="stat-value stat-critical">{{ $stats['critical'] }}</div>
        </div>
    </div>

    <h2>Items</h2>
    @forelse ($project->items as $item)
        <div class="item-card">
            <h3>{{ $item->title }}</h3>
            <div style="margin: 10px 0;">
                @if ($item->risk_level)
                    <span class="badge badge-{{ $item->risk_level }}">{{ strtoupper($item->risk_level) }} RISK</span>
                @endif
                <span class="badge" style="background: #f3f4f6; color: #374151;">{{ ucfirst($item->content_type) }}</span>
            </div>

            <div class="meta">
                <strong>Risk Score:</strong> {{ $item->risk_score ?? 'N/A' }}/100 |
                <strong>Status:</strong> {{ ucfirst($item->status) }} |
                <strong>Audited:</strong> {{ $item->audited_at ? $item->audited_at->format('Y-m-d H:i:s') : 'Not audited' }}
            </div>

            @if ($item->risk_summary)
                <div style="margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 4px;">
                    <strong>Risk Summary:</strong><br>
                    {{ $item->risk_summary }}
                </div>
            @endif

            @if ($item->risk_breakdown)
                <div class="risk-breakdown">
                    <strong style="display: block; margin-bottom: 15px;">Risk Breakdown:</strong>
                    @foreach ($item->risk_breakdown as $category => $details)
                        <div class="risk-category">
                            <div class="risk-category-header">
                                <span class="risk-category-name">{{ ucwords(str_replace('_', ' ', $category)) }}</span>
                                <span class="risk-score {{ $details['score'] >= 8 ? 'score-high' : ($details['score'] >= 5 ? 'score-medium' : 'score-low') }}">
                                    {{ $details['score'] ?? 0 }}/10
                                </span>
                            </div>
                            @if (!empty($details['issues']))
                                <ul>
                                    @foreach ($details['issues'] as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($item->mitigation_suggestions && count($item->mitigation_suggestions) > 0)
                <div class="mitigation">
                    <h4>Mitigation Suggestions</h4>
                    <ul style="margin-left: 20px;">
                        @foreach ($item->mitigation_suggestions as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @empty
        <div class="item-card">
            <p style="color: #6b7280;">No items in this project.</p>
        </div>
    @endforelse

    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Ethics/Risk Audit Assistant &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
