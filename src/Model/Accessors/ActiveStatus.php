<?php

namespace MaDnh\LaravelDevHelper\Model\Accessors;


trait ActiveStatus
{
    /**
     * Get active status of model which support SoftDeletes
     * @return bool True is active, False is inactive
     */
    public function getActiveStatusAttribute()
    {
        if (!array_key_exists('deleted_at', $this->attributes)) {
            return true;
        }

        return empty($this->attributes['deleted_at']);
    }

    /**
     * Check if this model is active
     * @return bool
     */
    public function getIsActiveStatusAttribute()
    {
        return true === $this->activeStatus;
    }

    /**
     * Check if this model is inactive
     * @return bool
     */
    public function getIsInactiveStatusAttribute()
    {
        return false === $this->activeStatus;
    }

}