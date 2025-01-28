<?php

namespace App\Contracts;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Model;

interface FaqRepositoryContract
{
    public function index(CanHaveManyFaqs $model): \Illuminate\Database\Eloquent\Collection;

    public function show(CanHaveManyFaqs $model, Faq $faq): Model|\Illuminate\Database\Eloquent\Relations\MorphMany;

    public function store(array $validated, CanHaveManyFaqs $model): array;

    public function update(array $validated, CanHaveManyFaqs $model): array;

    public function destroy(array $validated, CanHaveManyFaqs $model): bool|int|null;

    public function destroyManyFaqs(array $validated, CanHaveManyFaqs $model): CanHaveManyFaqs;

    public function destroyManyFaqDetails(array $validated, Faq $faq): Faq;
}
