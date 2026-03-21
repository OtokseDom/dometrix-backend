<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'organization_id' => $this->pivot?->organization_id,
            'user_id' => $this->pivot?->user_id,
            'role_id' => $this->pivot?->role_id,
            'organization' => $this->whenLoaded('organization', fn() => [
                'name' => $this->pivot?->name,
                'code' => $this->pivot?->code,
                'timezone' => $this->pivot?->timezone,
                'currency' => $this->pivot?->currency,
                'metadata' => $this->pivot?->metadata,
            ]),
            'user' => [
                'name' => $this->name,
                'email' => $this->email,
                'email_verified_at' => $this->email_verified_at,
                'is_active' => $this->is_active,
                'metadata' => $this->metadata,
            ],
            'role' => $this->pivot?->role?->only(['name', 'permissions']), // ensure pivot->role is loaded manually
            'created_at' => $this->pivot?->created_at?->toIso8601String(),
            'updated_at' => $this->pivot?->updated_at?->toIso8601String(),
        ];
    }
}
