<?php

namespace App\Rules;

use Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\InvokableRule;

class AttributesExistsInModel implements InvokableRule
{
    /**
     * @var array
     */
    protected Model $model; // The model to be used in the query

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Ensure the event is active
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        foreach ((array) $value as $column) {
            $hasColumn = Schema::hasColumn($this->model->getTable(), $column);

            if (! $hasColumn) {
                $fail("The column {$column} does not exist.");
            }
        }
    }
}
