<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TwoFactorAuthMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isEnabled = $this->isEnabledBy($request->user());

        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'has_enabled' => $isEnabled,
            'default' => $isEnabled ? $this->isDefault($request->user()) : false
        ];
    }
}
