<?php

namespace App\Observers;

use App\Jobs\RunEthicsAudit;
use App\Models\Item;

class ItemObserver
{
    /**
     * Handle the Item "created" event.
     * Automatically enqueue new items for ethics audit.
     */
    public function created(Item $item): void
    {
        if ($item->status === 'pending') {
            RunEthicsAudit::dispatch($item);
        }
    }

    /**
     * Handle the Item "updated" event.
     */
    public function updated(Item $item): void
    {
        //
    }

    /**
     * Handle the Item "deleted" event.
     */
    public function deleted(Item $item): void
    {
        //
    }

    /**
     * Handle the Item "restored" event.
     * Re-enqueue restored items if they're still pending.
     */
    public function restored(Item $item): void
    {
        if ($item->status === 'pending') {
            RunEthicsAudit::dispatch($item);
        }
    }

    /**
     * Handle the Item "force deleted" event.
     */
    public function forceDeleted(Item $item): void
    {
        //
    }
}
