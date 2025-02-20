<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class EnsureEntityBelongsToEntity implements DataAwareRule, InvokableRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];
    protected string $model; // The model to be used in the query
    protected string $relation; // The name of the relation to be used in the query
    protected string $foreignParam; // The name of the foreign param to be used in the query

    public function __construct(string $model, string $relation, string $foreignParam)
    {
        $this->model = $model;
        $this->relation = $relation;
        $this->foreignParam = $foreignParam;
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
        $paramValue = $this->data[$this->foreignParam] ?? null;

        if ($paramValue) {
            $query = $this->model::where('ref', $value)
                ->whereHas($this->relation, function ($query) use ($paramValue) {
                    $query->where('ref', $paramValue);
                });

            if ($query->doesntExist()) {
                $fail("The {$attribute} does not belong to the selected {$this->foreignParam}.");
                // TODO: LOG a message to notify the developer's on slack
            }
        }
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
 
        return $this;
    }
}
