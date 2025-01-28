<?php

namespace App\Traits\Drafts;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait DraftTrait
{

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootDraftTrait()
    {
        static::addGlobalScope(new DraftScope);

        static::creating(function ($model) {

            if (request('is_draft') == 1) {
                $model->drafted_at = now();
            }
        });
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeDraftTrait()
    {
        if (!isset($this->casts[$this->getDraftedAtColumn()])) {
            $this->casts[$this->getDraftedAtColumn()] = 'datetime';
        }
    }

    /**
     * mark as draft
     *
     * @return void
     */
    public function markAsDraft()
    {
        $this->{$this->getDraftedAtColumn()} = now();
        $this->save();
    }

    /**
     * mark as published
     *
     * @return void
     */
    public function markAsPublished()
    {
        $this->{$this->getDraftedAtColumn()} = null;
        $this->save();
    }


    public function isDrafted()
    {
        return !is_null($this->{$this->getDraftedAtColumn()});
    }

    /**
     * Get the fully qualified "published at" column.
     *
     * @param  string  $key
     * @return string
     */
    public function getQualifiedDraftedAtColumn()
    {
        return $this->getTable() . '.' . $this->getDraftedAtColumn();
    }

    /**
     * Get the name of the "published at" column.
     *
     * @return string
     */
    public function getDraftedAtColumn()
    {
        return 'drafted_at';
    }
    /**
     * The url on the website.
     *
     * @return Attribute
     */
    protected function draftUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return ($this->url && $this->isDrafted()) ? $this->url . '?draft=true' : null;
            },
        );
    }

    /**
     * Resolve the route binding for the given value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {

        return $this->where($field, $value)->withDrafted()->firstOrFail();
    }

}
