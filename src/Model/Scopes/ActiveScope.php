<?php

namespace MaDnh\LaravelDevHelper\Model\Scopes;


trait ActiveScope
{
    /**
     * Scope a query to only include popular users.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}