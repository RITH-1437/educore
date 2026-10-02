<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'area' => strstr($this->action, '.', true) ?: $this->action,
            'description' => $this->description,
            // Actor removed later: the row keeps a null actor and shows "System / removed user".
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? ['id' => $this->actor->id, 'name' => $this->actor->name, 'email' => $this->actor->email] : null),
            'target' => $this->auditable_type ? ['type' => class_basename($this->auditable_type), 'id' => $this->auditable_id] : null,
            'before' => $this->before_values,
            'after' => $this->after_values,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
