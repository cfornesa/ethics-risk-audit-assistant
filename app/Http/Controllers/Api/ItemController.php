<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunEthicsAudit;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with('project');

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('requires_review')) {
            $query->where('requires_human_review', $request->boolean('requires_review'));
        }

        $items = $query->latest()->paginate(20);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'content_type' => 'required|in:message,ad,script,post,other',
        ]);

        $validated['status'] = 'pending';

        $item = Item::create($validated);

        RunEthicsAudit::dispatch($item);

        return response()->json([
            'message' => 'Item created and queued for ethics audit',
            'item' => $item->fresh()->load('project'),
        ], 201);
    }

    public function show(Item $item)
    {
        $item->load('project');

        return response()->json($item);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'project_id' => 'sometimes|required|exists:projects,id',
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'content_type' => 'sometimes|required|in:message,ad,script,post,other',
        ]);

        $item->update($validated);

        if ($request->boolean('reaudit')) {
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

            return response()->json([
                'message' => 'Item updated and re-queued for ethics audit',
                'item' => $item->fresh()->load('project'),
            ]);
        }

        return response()->json([
            'message' => 'Item updated successfully',
            'item' => $item->fresh()->load('project'),
        ]);
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }

    public function reaudit(Item $item)
    {
        $item->update([
            'status' => 'pending',
            'audit_attempts' => 0,
            'last_error' => null,
        ]);

        RunEthicsAudit::dispatch($item);

        return response()->json([
            'message' => 'Item queued for re-audit',
            'item' => $item->fresh()->load('project'),
        ]);
    }

    public function markReviewed(Item $item)
    {
        $item->update([
            'requires_human_review' => false,
        ]);

        return response()->json([
            'message' => 'Item marked as reviewed',
            'item' => $item->fresh()->load('project'),
        ]);
    }
}
