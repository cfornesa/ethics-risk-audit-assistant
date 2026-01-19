<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Jobs\RunEthicsAudit;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('project')
            ->latest()
            ->paginate(20);

        return view('items.index', compact('items'));
    }

    public function create(Request $request)
    {
        $projects = Project::where('status', '!=', 'archived')->latest()->get();
        $selectedProject = $request->query('project_id');

        return view('items.create', compact('projects', 'selectedProject'));
    }

    public function store(StoreItemRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = 'pending';

        $item = Item::create($validated);

        return redirect()->route('items.show', $item)
            ->with('success', 'Item created and queued for ethics audit.');
    }

    public function show(Item $item)
    {
        $item->load('project');

        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        $projects = Project::where('status', '!=', 'archived')->latest()->get();

        return view('items.edit', compact('item', 'projects'));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());

        if ($request->has('reaudit')) {
            $item->update([
                'status' => 'pending',
                'risk_score' => null,
                'risk_level' => null,
                'risk_summary' => null,
                'risk_breakdown' => null,
                'mitigation_suggestions' => null,
                'llm_raw_response' => null,
                'audited_at' => null,
                'requires_human_review' => false,
                'notification_sent' => false,
            ]);

            RunEthicsAudit::dispatch($item);

            return redirect()->route('items.show', $item)
                ->with('success', 'Item updated and re-queued for ethics audit.');
        }

        return redirect()->route('items.show', $item)
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        $projectId = $item->project_id;
        $item->delete();

        return redirect()->route('projects.show', $projectId)
            ->with('success', 'Item deleted successfully.');
    }

    public function reaudit(Item $item)
    {
        $item->update([
            'status' => 'pending',
            'audit_attempts' => 0,
            'last_error' => null,
        ]);

        RunEthicsAudit::dispatch($item);

        return redirect()->route('items.show', $item)
            ->with('success', 'Item queued for re-audit.');
    }

    public function markReviewed(Item $item)
    {
        $item->update([
            'requires_human_review' => false,
        ]);

        return redirect()->route('items.show', $item)
            ->with('success', 'Item marked as reviewed.');
    }
}
