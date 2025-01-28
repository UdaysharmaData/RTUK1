<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait AddMetaTrait
{
    /**
     * @param array $data
     * @return Model
     */
    public function addMeta(array $data): Model
    {
        $formattedData['title'] = $data['title'] ?? null;
        $formattedData['description'] = $data['description'] ?? null;
        $formattedData['keywords'] = $data['keywords'] ?? null;
        $formattedData['robots'] = $data['robots'] ?? null;
        $formattedData['canonical_url'] = $data['canonical_url'] ?? null;
        $query = $this->meta();
        $action = $query->exists() ? 'update' : 'create';

        if (empty(array_filter($formattedData))) {
            $this->deleteMeta();
        } else {
            $query->{$action}($formattedData);
        }

        return $this->fresh();
    }

    /**
     * @return Model
     */
    public function deleteMeta(): Model
    {
        $this->meta()->delete();

        return $this->fresh();
    }
}
