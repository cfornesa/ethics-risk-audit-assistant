<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'title',
        'content',
        'content_type',
        'status',
        'risk_score',
        'risk_level',
        'risk_summary',
        'risk_breakdown',
        'mitigation_suggestions',
        'llm_raw_response',
        'llm_model',
        'audited_at',
        'requires_human_review',
        'notification_sent',
        'audit_attempts',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'risk_breakdown' => 'array',
        'mitigation_suggestions' => 'array',
        'metadata' => 'array',
        'audited_at' => 'datetime',
        'requires_human_review' => 'boolean',
        'notification_sent' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, ['high', 'critical']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRequiresReview($query)
    {
        return $query->where('requires_human_review', true);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'audited_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'audit_attempts' => $this->audit_attempts + 1,
        ]);
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'requires_review' => 'orange',
            default => 'gray',
        };
    }
}
