<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'organizations' => $this->organizations->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'code' => $org->code,
                    'pivot' => [
                        'role_id' => $org->pivot->role_id,
                        'status' => $org->pivot->status,
                    ]
                ];
            })
        ];
    }
}
