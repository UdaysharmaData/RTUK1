<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ref' => $this->ref,
            'gender' => $this->gender,
            'username' => $this->username,
            'dob' => $this->dob,
            'phone_number' => $this->phone_number,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'occupation' => $this->occupation,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'avatar' => new AvatarResource($this->upload)
        ];
    }
}
