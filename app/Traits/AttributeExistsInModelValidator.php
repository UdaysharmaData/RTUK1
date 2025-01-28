<?php

namespace App\Traits;

use App\Rules\AttributesExistsInModel;
use Illuminate\Database\Eloquent\Model;

trait AttributeExistsInModelValidator
{
    use FailedValidationResponseTrait;

    /**
     * @return array
     */
    public function attributeExistsInModelValidatorRule(Model $model): array
    {
        return [
            'extra_attributes' => ['sometimes', 'array',  new AttributesExistsInModel($model)],
            'extra_attributes.*' => ['string'],
        ];
    }

    /**
     * @return void
     */
    protected function attributeExistsInModelValidatorPrepareForValidation(): void
    {
        if (isset($this->extra_attributes)) {
            $this->merge([
                'extra_attributes' => explode(',', $this->extra_attributes)
            ]);
        }
    }

    /**
     * @param  Model $model
     * @return void
     */
    protected function attributeExistsInModelValidatorPassedValidation(Model $model): void
    {
        if (isset($this->extra_attributes)) {
            $tableName = $model->getTable();
            $attributes = [];

            foreach ($this->extra_attributes as $key => $attribute) {
                $attributes[$key] = $tableName . '.' . $attribute;
            }
        }
    }
}
