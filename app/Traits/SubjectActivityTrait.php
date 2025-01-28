<?php

namespace App\Traits;

use App\Enums\ActivityLogNameEnum;
use Spatie\Activitylog\ActivitylogServiceProvider;

trait SubjectActivityTrait
{
    /**
     * Log create activity
     *
     * @param  mixed $causedBy
     * @param  mixed$description
     * @param  mixed $properties
     * @return void
     */
    public function logCreate($causedBy, $description = null, $properties = [])
    {
        activity(ActivityLogNameEnum::Created->value)
            ->performedOn($this)
            ->causedBy($causedBy ?: auth()->user())
            ->withProperties($properties)
            ->log($description ?: 'created');
    }

    /**
     * Log update activity
     *
     * @param  mixed $oldData
     * @param  mixed $causedBy
     * @param  mixed$description
     * @param  mixed $properties
     * @return void
     */
    public function logUpdate($oldData, $description = null, $causedBy = null, $properties = [])
    {

        $properties[] = array_diff_assoc($this->toArray(), $oldData);

        activity(ActivityLogNameEnum::Updated->value)
            ->performedOn($this)
            ->causedBy($causedBy ?: auth()->user())
            ->withProperties($properties)
            ->log($description ?: 'updated');
    }

    /**
     * Log delete activity
     *
     * @param  mixed $causedBy
     * @param  mixed$description
     * @param  mixed $properties
     * @return void
     */
    public function logDelete($description = null, $causedBy = null, $properties = [])
    {
        activity(ActivityLogNameEnum::Deleted->value)
            ->performedOn($this)
            ->causedBy($causedBy ?: auth()->user())
            ->withProperties($properties)
            ->log($description ?: 'deleted');
    }

    /**
     * Log restore activity
     *
     * @param  mixed $causedBy
     * @param  mixed$description
     * @param  mixed $properties
     * @return void
     */
    public function logRestore($description = null, $causedBy = null, $properties = [])
    {
        activity(ActivityLogNameEnum::Restored->value)
            ->performedOn($this)
            ->causedBy($causedBy ?: auth()->user())
            ->withProperties($properties)
            ->log($description ?: 'restored');
    }

    /**
     * Log activity
     *
     * @param  mixed $logName
     * @param  mixed $causedBy
     * @param  mixed$description
     * @param  mixed $properties
     * @return void
     */
    public function logCustom(ActivityLogNameEnum $logName, $description = null, $properties = [], $causedBy = null)
    {
        activity($logName->value)
            ->performedOn($this)
            ->causedBy($causedBy ?: auth()->user())
            ->withProperties($properties)
            ->log($description ?: $logName->value);
    }

    public function activities()
    {
        return $this->morphMany(
            ActivitylogServiceProvider::determineActivityModel(),
            'subject'
        );
    }
}
