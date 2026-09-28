<?php

namespace App\Http\Resources;

use App\Dto\User\UserData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User|UserData
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource instanceof UserData
            ? $this->resource
            : UserData::fromModel($this->resource, $this->resource->relationLoaded('role'));

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->isActive,
            'last_login_at' => $user->lastLoginAt,
            'created_at' => $user->createdAt,
            'role' => $this->when($user->hasRole(), fn () => [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
            ]),
        ];
    }
}
