<?php


namespace MaDnh\LaravelDevHelper\Model\Scopes;


trait DeletedScope
{
    /**
     * Scope a query to only include popular users.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }
}