<?php

namespace App\Traits;

use Illuminate\Validation\Rule;

trait DraftCustomValidator {
    
        use FailedValidationResponseTrait;
    
        /**
        * @return array
        */
        public function draftRules(): array
        {
            return [
                'is_draft' => ['sometimes', 'nullable', 'boolean'],
            ];
        }
    
        /**
        * @return array
        */
        public function draftMessages(): array
        {
            return [
                'is_draft.boolean' => 'The is draft field must be true or false.',
            ];
        }

        public function draftBodyParameters(): array
        {
            return [
                'is_draft' => [
                    'description' => 'save the new entity as a draft or not',
                    'example' => true
                ]
            ];
        }

        
        /**
         * markAsPublishedValidationRules
         *
         * @return array
         */
        public function markAsPublishedValidationRules($table): array
        {
            return [
                'ids' => ['required', 'array'],
                'ids.*' => ['required', 'integer', Rule::exists($table, 'id')->whereNotNull('drafted_at')]
            ];
        }

        
        /**
         * markAsDraftValidationRules
         *
         * @param  mixed $table
         * @return array
         */
        public function markAsDraftValidationRules($table): array
        {
            return [
                'ids' => ['required', 'array'],
                'ids.*' => ['required', 'integer', Rule::exists($table, 'id')->whereNull('drafted_at')]
            ];
        }
    
}