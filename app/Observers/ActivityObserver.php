<?php

namespace App\Observers;

use App\Models\Activity;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class ActivityObserver
{
    public function __construct(protected GoogleCalendarService $calendarService)
    {
    }

    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        $this->calendarService->syncActivity($activity);
    }

    /**
     * Handle the Activity "updated" event.
     */
    public function updated(Activity $activity): void
    {
        // Only sync if relevant fields changed
        $relevantFields = ['name', 'description', 'start_time', 'end_time', 'latitude', 'longitude'];
        if ($activity->wasChanged($relevantFields)) {
            $this->calendarService->syncActivity($activity);
        }
    }

    /**
     * Handle the Activity "deleted" event.
     */
    public function deleted(Activity $activity): void
    {
        $this->calendarService->deleteActivity($activity);
    }
}
