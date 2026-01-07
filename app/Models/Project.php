<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function highRiskItems(): HasMany
    {
        return $this->hasMany(Item::class)->whereIn('risk_level', ['high', 'critical']);
    }

    public function pendingItems(): HasMany
    {
        return $this->hasMany(Item::class)->where('status', 'pending');
    }

    public function getRiskStatisticsAttribute(): array
    {
        $items = $this->items;

        return [
            'total' => $items->count(),
            'low' => $items->where('risk_level', 'low')->count(),
            'medium' => $items->where('risk_level', 'medium')->count(),
            'high' => $items->where('risk_level', 'high')->count(),
            'critical' => $items->where('risk_level', 'critical')->count(),
            'pending' => $items->where('status', 'pending')->count(),
            'completed' => $items->where('status', 'completed')->count(),
        ];
    }
}
