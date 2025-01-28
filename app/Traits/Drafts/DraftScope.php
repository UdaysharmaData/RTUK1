<?php

namespace App\Traits\Drafts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class DraftScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = [ 'MarkAsDraft', 'MarkAsPublished', 'WithDrafted', 'OnlyDrafted', 'WithoutDrafted'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder $builder
     * @param  Model   $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereNull($model->getQualifiedDraftedAtColumn());
    }
    
    /**
     * Extend the query builder with the needed functions.
     *
     * @param  Builder $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }


    /**
     * Add the markAsPublished extension to the builder.
     *
     * @param  Builder $builder
     * @return void
     */
    protected function addMarkAsPublished(Builder $builder)
    {
        $builder->macro('markAsPublished', function (Builder $builder) {
            $builder->onlyDrafted();

            return $builder->update([
                $builder->getModel()->getDraftedAtColumn() => null,
            ]);
        });
    }

    /**
     * Add the markAsDraft extension to the builder.
     *
     * @param  Builder $builder
     * @return void
     */
    protected function addMarkAsDraft(Builder $builder)
    {
        $builder->macro('markAsDraft', function (Builder $builder) {

            return $builder->update([
                $builder->getModel()->getDraftedAtColumn() => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Add the with-Drafted extension to the builder.
     *
     * @param  Builder $builder
     * @return void
     */
    protected function addWithDrafted(Builder $builder)
    {
        $builder->macro('withDrafted', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the only-Drafted extension to the builder.
     *
     * @param  Builder $builder
     * @return void
     */
    protected function addOnlyDrafted(Builder $builder)
    {
        $builder->macro('onlyDrafted', function (Builder $builder) {
            $model = $builder->getModel();
    
            $builder->withoutGlobalScope($this)->whereNotNull($model->getQualifiedDraftedAtColumn());
    
            return $builder;
        });
    }    

    /**
     * Add the without-Drafted extension to the builder.
     *
     * @param  Builder $builder
     * @return void
     */
    protected function addWithoutDrafted(Builder $builder)
    {
        $builder->macro('withoutDrafted', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNull($model->getDraftedAtColumn());

            return $builder;
        });
    }


}