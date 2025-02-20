<?php

namespace App\Contracts;

interface ListingQueryParamsRequestContract
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;
}
