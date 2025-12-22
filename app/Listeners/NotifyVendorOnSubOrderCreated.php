<?php

namespace App\Listeners;

use App\Events\SubOrderCreated;
use App\Jobs\NotifyVendorJob;

class NotifyVendorOnSubOrderCreated
{
    /**
     * Handle the event.
     */
    public function handle(SubOrderCreated $event): void
    {
        NotifyVendorJob::dispatch($event->subOrder);
    }
}
